<?php
/**
 * Titre: Page de paramètres utilisateur - Version complète
 * Chemin: /public/user/settings.php
 * Version: 0.5 beta + build auto
 */

session_start();

// Configuration - ROOT_PATH corrigé
define('ROOT_PATH', dirname(dirname(__DIR__)));

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
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    $current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
}

// Variables pour template
$version_info = getVersionInfo();
$page_title = 'Paramètres';
$page_subtitle = 'Configuration personnelle';
$current_module = 'settings';
$user_authenticated = true;
$module_css = true;

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '👤', 'text' => 'Mon Espace', 'url' => '/user/', 'active' => false],
    ['icon' => '⚙️', 'text' => 'Paramètres', 'url' => '/user/settings.php', 'active' => true]
];

// Paramètres par défaut
$default_settings = [
    'theme' => 'light',
    'language' => 'fr',
    'notifications_email' => true,
    'notifications_browser' => true,
    'items_per_page' => 25,
    'default_module' => 'calculateur',
    'auto_save' => true,
    'show_tips' => true,
    'compact_mode' => false,
    'sidebar_collapsed' => false,
    'timezone' => 'Europe/Paris',
    'date_format' => 'dd/mm/yyyy'
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
            $user_settings['timezone'] = $_POST['timezone'] ?? 'Europe/Paris';
            $user_settings['date_format'] = $_POST['date_format'] ?? 'dd/mm/yyyy';
            $message = 'Paramètres généraux sauvegardés';
            break;
            
        case 'save_interface':
            $user_settings['theme'] = $_POST['theme'] ?? 'light';
            $user_settings['compact_mode'] = isset($_POST['compact_mode']);
            $user_settings['sidebar_collapsed'] = isset($_POST['sidebar_collapsed']);
            $user_settings['show_tips'] = isset($_POST['show_tips']);
            $message = 'Interface mise à jour';
            break;
            
        case 'save_notifications':
            $user_settings['notifications_email'] = isset($_POST['notifications_email']);
            $user_settings['notifications_browser'] = isset($_POST['notifications_browser']);
            $message = 'Notifications mises à jour';
            break;
            
        case 'save_advanced':
            $user_settings['auto_save'] = isset($_POST['auto_save']);
            $message = 'Paramètres avancés mis à jour';
            break;
            
        case 'reset':
            if ($_POST['confirm'] === 'RESET') {
                $user_settings = $default_settings;
                $message = 'Paramètres remis à zéro';
            } else {
                $error = 'Confirmation incorrecte';
            }
            break;
            
        case 'export':
            // Export des paramètres
            $export_data = [
                'settings' => $user_settings,
                'user' => [
                    'username' => $current_user['username'],
                    'role' => $current_user['role']
                ],
                'export_date' => date('Y-m-d H:i:s'),
                'version' => APP_VERSION
            ];
            
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="guldagil_settings_' . date('Y-m-d') . '.json"');
            echo json_encode($export_data, JSON_PRETTY_PRINT);
            exit;
            
        case 'import':
            if (isset($_FILES['settings_file']) && $_FILES['settings_file']['error'] === UPLOAD_ERR_OK) {
                $import_data = json_decode(file_get_contents($_FILES['settings_file']['tmp_name']), true);
                
                if ($import_data && isset($import_data['settings'])) {
                    $user_settings = array_merge($default_settings, $import_data['settings']);
                    $message = 'Paramètres importés avec succès';
                } else {
                    $error = 'Fichier de paramètres invalide';
                }
            } else {
                $error = 'Erreur lors de l\'upload du fichier';
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
            <a href="#advanced" class="nav-item" data-tab="advanced">
                <span>🔬</span> Avancé
            </a>
            <a href="#import-export" class="nav-item" data-tab="import-export">
                <span>📁</span> Import/Export
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
                            <option value="es" <?= $user_settings['language'] === 'es' ? 'selected' : '' ?>>Español</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>📄 Éléments par page</label>
                        <select name="items_per_page">
                            <option value="10" <?= $user_settings['items_per_page'] === 10 ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= $user_settings['items_per_page'] === 25 ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= $user_settings['items_per_page'] === 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $user_settings['items_per_page'] === 100 ? 'selected' : '' ?>>100</option>
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
                    
                    <div class="form-group">
                        <label>🕐 Fuseau horaire</label>
                        <select name="timezone">
                            <option value="Europe/Paris" <?= $user_settings['timezone'] === 'Europe/Paris' ? 'selected' : '' ?>>Europe/Paris</option>
                            <option value="UTC" <?= $user_settings['timezone'] === 'UTC' ? 'selected' : '' ?>>UTC</option>
                            <option value="America/New_York" <?= $user_settings['timezone'] === 'America/New_York' ? 'selected' : '' ?>>America/New_York</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>📅 Format de date</label>
                        <select name="date_format">
                            <option value="dd/mm/yyyy" <?= $user_settings['date_format'] === 'dd/mm/yyyy' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                            <option value="mm/dd/yyyy" <?= $user_settings['date_format'] === 'mm/dd/yyyy' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                            <option value="yyyy-mm-dd" <?= $user_settings['date_format'] === 'yyyy-mm-dd' ? 'selected' : '' ?>>YYYY-MM-DD</option>
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
                            
                            <label class="theme-option">
                                <input type="radio" name="theme" value="auto" <?= $user_settings['theme'] === 'auto' ? 'checked' : '' ?>>
                                <div class="theme-preview auto">
                                    <div class="preview-header"></div>
                                    <div class="preview-content"></div>
                                </div>
                                <span>Auto</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="compact_mode" <?= $user_settings['compact_mode'] ? 'checked' : '' ?>>
                            📱 Mode compact
                        </label>
                        <small>Interface plus dense avec moins d'espacement</small>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="sidebar_collapsed" <?= $user_settings['sidebar_collapsed'] ? 'checked' : '' ?>>
                            📋 Sidebar réduite par défaut
                        </label>
                        <small>La barre latérale sera réduite au démarrage</small>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="show_tips" <?= $user_settings['show_tips'] ? 'checked' : '' ?>>
                            💡 Afficher les conseils
                        </label>
                        <small>Bulles d'aide et conseils d'utilisation</small>
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
                        <label>
                            <input type="checkbox" name="notifications_email" <?= $user_settings['notifications_email'] ? 'checked' : '' ?>>
                            📧 Notifications par email
                        </label>
                        <small>Recevoir les alertes importantes par email</small>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="notifications_browser" <?= $user_settings['notifications_browser'] ? 'checked' : '' ?>>
                            🔔 Notifications navigateur
                        </label>
                        <small>Notifications push dans le navigateur</small>
                    </div>
                    
                    <button type="submit" class="btn primary">🔔 Sauvegarder</button>
                </form>
            </section>

            <!-- Avancé -->
            <section id="advanced" class="tab-section">
                <h2>Paramètres avancés</h2>
                
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="save_advanced">
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="auto_save" <?= $user_settings['auto_save'] ? 'checked' : '' ?>>
                            💾 Sauvegarde automatique
                        </label>
                        <small>Sauvegarde automatique des formulaires</small>
                    </div>
                    
                    <div class="info-section">
                        <h3>Informations système</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Version :</span>
                                <span class="info-value"><?= APP_VERSION ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Build :</span>
                                <span class="info-value"><?= substr(BUILD_NUMBER, -8) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Utilisateur :</span>
                                <span class="info-value"><?= htmlspecialchars($current_user['username']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Rôle :</span>
                                <span class="info-value"><?= htmlspecialchars($current_user['role']) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn primary">🔬 Sauvegarder</button>
                </form>
            </section>

            <!-- Import/Export -->
            <section id="import-export" class="tab-section">
                <h2>Import/Export des paramètres</h2>
                
                <div class="import-export-section">
                    <div class="export-section">
                        <h3>📤 Export</h3>
                        <p>Exporter vos paramètres vers un fichier JSON</p>
                        <form method="POST">
                            <input type="hidden" name="action" value="export">
                            <button type="submit" class="btn primary">📥 Télécharger mes paramètres</button>
                        </form>
                    </div>
                    
                    <div class="import-section">
                        <h3>📥 Import</h3>
                        <p>Importer des paramètres depuis un fichier JSON</p>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="import">
                            <div class="form-group">
                                <input type="file" name="settings_file" accept=".json" required>
                            </div>
                            <button type="submit" class="btn warning">📤 Importer paramètres</button>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Réinitialisation -->
            <section id="reset" class="tab-section">
                <h2>Réinitialisation</h2>
                
                <div class="danger-zone">
                    <h3>⚠️ Zone dangereuse</h3>
                    <p>Cette action remettra tous vos paramètres aux valeurs par défaut et supprimera toute personnalisation.</p>
                    
                    <form method="POST" class="settings-form" onsubmit="return confirm('Êtes-vous sûr de vouloir réinitialiser tous vos paramètres ?')">
                        <input type="hidden" name="action" value="reset">
                        
                        <div class="form-group">
                            <label>Tapez "RESET" pour confirmer :</label>
                            <input type="text" name="confirm" placeholder="RESET" required>
                        </div>
                        
                        <button type="submit" class="btn danger">🔄 Réinitialiser tout</button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- CSS externe -->
<link rel="stylesheet" href="assets/css/user.css?v=<?= $build_number ?>">

<!-- JavaScript externe -->
<script src="assets/js/user.js?v=<?= $build_number ?>"></script>

<?php
// Inclure footer
include ROOT_PATH . '/templates/footer.php';
?>

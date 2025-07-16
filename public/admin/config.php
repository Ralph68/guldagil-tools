<?php
/**
 * Titre: Gestionnaire de configuration APP - Administration
 * Chemin: /public/admin/config.php
 * Version: 0.5 beta + build auto
 */

// Sécurité et initialisation
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

session_start();

// Chargement config
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Authentification admin requise
$user_authenticated = false;
$current_user = null;

try {
    if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
        require_once ROOT_PATH . '/core/auth/AuthManager.php';
        $auth = new AuthManager();
        
        if ($auth->isAuthenticated()) {
            $current_user = $auth->getCurrentUser();
            $user_authenticated = $current_user['role'] === 'admin';
        }
    }
} catch (Exception $e) {
    // Fallback temporaire pour dev
    if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
        $user_authenticated = true;
        $current_user = $_SESSION['user'];
    }
}

if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Variables pour template
$page_title = 'Configuration Système';
$current_module = 'admin';

// Messages
$message = '';
$error = '';

// Configuration éditable
$config_file = ROOT_PATH . '/config/version.php';
$editable_configs = [
    'APP_NAME' => [
        'label' => 'Nom de l\'application',
        'type' => 'text',
        'value' => defined('APP_NAME') ? APP_NAME : '',
        'required' => true,
        'help' => 'Nom complet affiché dans le portail'
    ],
    'APP_VERSION' => [
        'label' => 'Version',
        'type' => 'text',
        'value' => defined('APP_VERSION') ? APP_VERSION : '',
        'required' => true,
        'pattern' => '^\d+\.\d+(\.\d+)?(-\w+)?$',
        'help' => 'Format: X.Y ou X.Y.Z ou X.Y.Z-beta'
    ],
    'APP_AUTHOR' => [
        'label' => 'Auteur',
        'type' => 'text',
        'value' => defined('APP_AUTHOR') ? APP_AUTHOR : '',
        'required' => true,
        'help' => 'Nom de l\'auteur/développeur'
    ],
    'APP_ENV' => [
        'label' => 'Environnement',
        'type' => 'select',
        'value' => defined('APP_ENV') ? APP_ENV : 'development',
        'options' => [
            'development' => 'Développement',
            'staging' => 'Test/Recette',
            'production' => 'Production'
        ],
        'required' => true,
        'help' => 'Environnement d\'exécution'
    ]
];

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'update_config') {
        
        try {
            // Validation des données
            $new_values = [];
            foreach ($editable_configs as $key => $config) {
                $value = trim($_POST[$key] ?? '');
                
                if ($config['required'] && empty($value)) {
                    throw new Exception("Le champ '{$config['label']}' est requis");
                }
                
                if (!empty($config['pattern']) && !preg_match('/' . $config['pattern'] . '/', $value)) {
                    throw new Exception("Format invalide pour '{$config['label']}'");
                }
                
                $new_values[$key] = $value;
            }
            
            // Lecture du fichier actuel
            if (!file_exists($config_file)) {
                throw new Exception("Fichier de configuration non trouvé");
            }
            
            $content = file_get_contents($config_file);
            
            // Mise à jour des valeurs
            foreach ($new_values as $key => $value) {
                $escaped_value = addslashes($value);
                $pattern = "/define\(\s*'$key'\s*,\s*'[^']*'\s*\);/";
                $replacement = "define('$key', '$escaped_value');";
                
                if (preg_match($pattern, $content)) {
                    $content = preg_replace($pattern, $replacement, $content);
                } else {
                    // Ajouter la constante si elle n'existe pas
                    $insert_point = "define('APP_VERSION'";
                    if (strpos($content, $insert_point) !== false) {
                        $new_line = "\ndefine('$key', '$escaped_value');";
                        $content = str_replace($insert_point, $new_line . "\n" . $insert_point, $content);
                    }
                }
            }
            
            // Sauvegarde avec backup
            $backup_file = $config_file . '.backup.' . date('Y-m-d_H-i-s');
            if (!copy($config_file, $backup_file)) {
                throw new Exception("Impossible de créer le backup");
            }
            
            if (file_put_contents($config_file, $content) === false) {
                throw new Exception("Impossible d'écrire le fichier de configuration");
            }
            
            // Mise à jour des valeurs affichées
            foreach ($new_values as $key => $value) {
                $editable_configs[$key]['value'] = $value;
            }
            
            $message = "Configuration mise à jour avec succès. Backup créé : " . basename($backup_file);
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
    
    elseif ($_POST['action'] === 'restore_backup') {
        
        $backup_file = $_POST['backup_file'] ?? '';
        $backup_path = ROOT_PATH . '/config/' . basename($backup_file);
        
        if (file_exists($backup_path) && strpos($backup_file, 'version.php.backup.') === 0) {
            if (copy($backup_path, $config_file)) {
                $message = "Configuration restaurée depuis le backup : $backup_file";
                // Recharger la page pour afficher les nouvelles valeurs
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $error = "Impossible de restaurer le backup";
            }
        } else {
            $error = "Fichier de backup invalide";
        }
    }
}

// Liste des backups
$backups = [];
$config_dir = ROOT_PATH . '/config/';
if (is_dir($config_dir)) {
    $files = scandir($config_dir);
    foreach ($files as $file) {
        if (preg_match('/^version\.php\.backup\.(.+)$/', $file, $matches)) {
            $backups[] = [
                'file' => $file,
                'date' => $matches[1],
                'formatted_date' => date('d/m/Y H:i:s', strtotime(str_replace('_', ' ', str_replace('-', '/', $matches[1])))),
                'size' => filesize($config_dir . $file)
            ];
        }
    }
    usort($backups, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - <?= defined('APP_NAME') ? APP_NAME : 'Admin' ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd') ?>">
    <link rel="stylesheet" href="/public/admin/assets/css/admin.css?v=<?= defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd') ?>">
    
    <style>
        .config-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .config-section {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-grid {
            display: grid;
            gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
        }
        
        .form-input, .form-select {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
        }
        
        .form-help {
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
        }
        
        .alert-error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }
        
        .backup-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .backup-table th,
        .backup-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .backup-table th {
            background: #f9fafb;
            font-weight: 600;
        }
        
        .current-config {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        
        .config-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .config-item {
            display: flex;
            flex-direction: column;
        }
        
        .config-item strong {
            color: #1f2937;
            font-size: 0.875rem;
        }
        
        .config-item span {
            color: #4b5563;
            font-size: 0.8rem;
        }
    </style>
</head>

<body>
    <?php include ROOT_PATH . '/templates/header.php'; ?>
    
    <main class="config-container">
        
        <!-- En-tête -->
        <div class="config-section">
            <h1>⚙️ Configuration Système</h1>
            <p>Gestion des paramètres de l'application. Les modifications prennent effet immédiatement.</p>
        </div>
        
        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <!-- Configuration actuelle -->
        <div class="current-config">
            <h3>📊 Configuration actuelle</h3>
            <div class="config-info">
                <div class="config-item">
                    <strong>Application</strong>
                    <span><?= defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'Non défini' ?></span>
                </div>
                <div class="config-item">
                    <strong>Version</strong>
                    <span><?= defined('APP_VERSION') ? htmlspecialchars(APP_VERSION) : 'Non défini' ?></span>
                </div>
                <div class="config-item">
                    <strong>Build</strong>
                    <span><?= defined('BUILD_NUMBER') ? htmlspecialchars(BUILD_NUMBER) : 'Non défini' ?></span>
                </div>
                <div class="config-item">
                    <strong>Environnement</strong>
                    <span><?= defined('APP_ENV') ? htmlspecialchars(APP_ENV) : 'Non défini' ?></span>
                </div>
                <div class="config-item">
                    <strong>Auteur</strong>
                    <span><?= defined('APP_AUTHOR') ? htmlspecialchars(APP_AUTHOR) : 'Non défini' ?></span>
                </div>
                <div class="config-item">
                    <strong>Dernière modification</strong>
                    <span><?= date('d/m/Y H:i:s', filemtime($config_file)) ?></span>
                </div>
            </div>
        </div>
        
        <!-- Formulaire de modification -->
        <div class="config-section">
            <h2>✏️ Modifier la configuration</h2>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_config">
                
                <div class="form-grid">
                    <?php foreach ($editable_configs as $key => $config): ?>
                        <div class="form-group">
                            <label class="form-label" for="<?= $key ?>">
                                <?= htmlspecialchars($config['label']) ?>
                                <?php if ($config['required']): ?>
                                    <span style="color: #ef4444;">*</span>
                                <?php endif; ?>
                            </label>
                            
                            <?php if ($config['type'] === 'select'): ?>
                                <select name="<?= $key ?>" id="<?= $key ?>" class="form-select" <?= $config['required'] ? 'required' : '' ?>>
                                    <?php foreach ($config['options'] as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= $config['value'] === $value ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="<?= $config['type'] ?>" 
                                       name="<?= $key ?>" 
                                       id="<?= $key ?>" 
                                       class="form-input"
                                       value="<?= htmlspecialchars($config['value']) ?>"
                                       <?= $config['required'] ? 'required' : '' ?>
                                       <?= !empty($config['pattern']) ? 'pattern="' . htmlspecialchars($config['pattern']) . '"' : '' ?>>
                            <?php endif; ?>
                            
                            <?php if (!empty($config['help'])): ?>
                                <span class="form-help"><?= htmlspecialchars($config['help']) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">💾 Enregistrer les modifications</button>
                    <a href="/admin/" class="btn btn-secondary">← Retour admin</a>
                </div>
            </form>
        </div>
        
        <!-- Gestion des backups -->
        <div class="config-section">
            <h2>🗄️ Gestion des sauvegardes</h2>
            <p>Sauvegardes automatiques créées avant chaque modification.</p>
            
            <?php if (!empty($backups)): ?>
                <table class="backup-table">
                    <thead>
                        <tr>
                            <th>Date de création</th>
                            <th>Taille</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($backups, 0, 10) as $backup): ?>
                            <tr>
                                <td><?= htmlspecialchars($backup['formatted_date']) ?></td>
                                <td><?= number_format($backup['size'] / 1024, 1) ?> KB</td>
                                <td>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir restaurer cette sauvegarde ?')">
                                        <input type="hidden" name="action" value="restore_backup">
                                        <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['file']) ?>">
                                        <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">
                                            🔄 Restaurer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (count($backups) > 10): ?>
                    <p style="margin-top: 1rem; color: #6b7280; font-size: 0.875rem;">
                        ... et <?= count($backups) - 10 ?> autres sauvegardes plus anciennes
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p style="color: #6b7280;">Aucune sauvegarde trouvée.</p>
            <?php endif; ?>
        </div>
        
        <!-- Informations techniques -->
        <div class="config-section">
            <h2>🔧 Informations techniques</h2>
            <div class="config-info">
                <div class="config-item">
                    <strong>Fichier de configuration</strong>
                    <span><?= $config_file ?></span>
                </div>
                <div class="config-item">
                    <strong>Permissions</strong>
                    <span><?= substr(sprintf('%o', fileperms($config_file)), -4) ?></span>
                </div>
                <div class="config-item">
                    <strong>Propriétaire</strong>
                    <span><?= function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($config_file))['name'] : 'N/A' ?></span>
                </div>
                <div class="config-item">
                    <strong>Écritable</strong>
                    <span><?= is_writable($config_file) ? '✅ Oui' : '❌ Non' ?></span>
                </div>
            </div>
        </div>
        
    </main>
    
    <?php include ROOT_PATH . '/templates/footer.php'; ?>
    
    <script>
        // Validation côté client
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[method="POST"]');
            const versionInput = document.getElementById('APP_VERSION');
            
            if (versionInput) {
                versionInput.addEventListener('input', function() {
                    const pattern = /^\d+\.\d+(\.\d+)?(-\w+)?$/;
                    if (this.value && !pattern.test(this.value)) {
                        this.setCustomValidity('Format de version invalide (ex: 1.0, 1.0.0, 1.0.0-beta)');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
            
            // Confirmation avant soumission
            form?.addEventListener('submit', function(e) {
                if (this.querySelector('input[name="action"][value="update_config"]')) {
                    if (!confirm('Êtes-vous sûr de vouloir modifier la configuration ?')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>

<?php
/**
 * Diagnostic système - À SUPPRIMER après validation
 * Chemin: /public/system-check.php
 * ATTENTION: Supprimer ce fichier en production !
 */

// Sécurité basique
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', 'localhost'])) {
    die('Accès refusé');
}

echo '<!DOCTYPE html><html><head><title>Diagnostic Système</title>';
echo '<style>body{font-family:monospace;margin:2rem;}';
echo '.ok{color:green;}.error{color:red;}.warning{color:orange;}</style></head><body>';
echo '<h1>🔧 Diagnostic Système Guldagil</h1>';

// Tests configuration
echo '<h2>Configuration</h2>';
echo '<div class="' . (file_exists('../config/config.php') ? 'ok' : 'error') . '">';
echo file_exists('../config/config.php') ? '✅' : '❌';
echo ' config/config.php</div>';

echo '<div class="' . (file_exists('../config/version.php') ? 'ok' : 'error') . '">';
echo file_exists('../config/version.php') ? '✅' : '❌';
echo ' config/version.php</div>';

// Tests authentification
echo '<h2>Authentification</h2>';
echo '<div class="' . (file_exists('../core/auth/AuthManager.php') ? 'ok' : 'warning') . '">';
echo file_exists('../core/auth/AuthManager.php') ? '✅' : '⚠️';
echo ' core/auth/AuthManager.php</div>';

echo '<div class="' . (file_exists('auth/login.php') ? 'ok' : 'error') . '">';
echo file_exists('auth/login.php') ? '✅' : '❌';
echo ' auth/login.php</div>';

// Tests CSS
echo '<h2>Assets</h2>';
echo '<div class="' . (file_exists('assets/css/portal.css') ? 'ok' : 'error') . '">';
echo file_exists('assets/css/portal.css') ? '✅' : '❌';
echo ' assets/css/portal.css</div>';

// Tests sécurité
echo '<h2>Sécurité</h2>';
$login_content = file_exists('auth/login.php') ? file_get_contents('auth/login.php') : '';
$has_hardcoded = preg_match('/password.*=.*["\'][^"\']+["\']/', $login_content);
echo '<div class="' . (!$has_hardcoded ? 'ok' : 'error') . '">';
echo !$has_hardcoded ? '✅' : '❌';
echo ' Aucun mot de passe en dur détecté</div>';

// Tests base de données (si config existe)
if (file_exists('../config/config.php')) {
    try {
        require_once '../config/config.php';
        if (function_exists('getDB')) {
            $db = getDB();
            echo '<div class="ok">✅ Connexion base de données OK</div>';
            
            // Vérifier tables auth
            $tables = $db->query("SHOW TABLES LIKE 'auth_%'")->fetchAll();
            echo '<div class="' . (count($tables) >= 2 ? 'ok' : 'warning') . '">';
            echo count($tables) >= 2 ? '✅' : '⚠️';
            echo ' Tables authentification (' . count($tables) . ' trouvées)</div>';
        }
    } catch (Exception $e) {
        echo '<div class="error">❌ Erreur BDD: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Tests permissions
echo '<h2>Permissions</h2>';
$writable_dirs = ['../storage/logs', '../storage/cache', '../storage/temp'];
foreach ($writable_dirs as $dir) {
    $writable = is_dir($dir) && is_writable($dir);
    echo '<div class="' . ($writable ? 'ok' : 'warning') . '">';
    echo $writable ? '✅' : '⚠️';
    echo ' ' . basename($dir) . ' (écriture)</div>';
}

echo '<h2>Actions recommandées</h2>';
echo '<ul>';
echo '<li>Supprimer ce fichier system-check.php après validation</li>';
echo '<li>Configurer les tables auth_users et auth_sessions</li>';
echo '<li>Créer un compte administrateur</li>';
echo '<li>Activer HTTPS en production</li>';
echo '<li>Vérifier les permissions des dossiers storage/</li>';
echo '</ul>';

echo '</body></html>';
?>

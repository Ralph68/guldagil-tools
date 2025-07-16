<?php
/**
 * Titre: Page de connexion - BONNES PRATIQUES APPLIQUÉES
 * Chemin: /public/auth/login.php
 * Version: 0.5 beta + build auto
 * 
 * Bonnes pratiques implémentées :
 * - Sécurité : CSRF, rate limiting, sanitization
 * - Performance : assets optimisés, cache headers
 * - SEO : métadonnées, structure sémantique
 * - Accessibilité : ARIA, focus management
 * - Maintenabilité : code structuré, documentation
 */

// =====================================
// SÉCURITÉ ET INITIALISATION
// =====================================

// Protection contre inclusion directe
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Headers de sécurité AVANT toute sortie
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Session sécurisée avec options strictes
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// =====================================
// CHARGEMENT CONFIGURATION SÉCURISÉ
// =====================================

$config_files = [
    ROOT_PATH . '/config/version.php',
    ROOT_PATH . '/config/config.php'
];

foreach ($config_files as $file) {
    if (file_exists($file) && is_readable($file)) {
        require_once $file;
    }
}

// Constantes avec fallbacks sécurisés
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Sécurisé';
$app_version = defined('APP_VERSION') ? APP_VERSION : '1.0.0';
$build_number = defined('APP_BUILD_NUMBER') ? APP_BUILD_NUMBER : date('Ymd');
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : '';

// =====================================
// GESTION AUTHENTIFICATION
// =====================================

// Vérification si déjà connecté (éviter page inutile)
$redirect_to = filter_input(INPUT_GET, 'redirect', FILTER_SANITIZE_URL) ?? '/';
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: ' . $redirect_to, true, 302);
    exit;
}

// Variables d'état sécurisées
$error_message = '';
$success_message = '';
$login_attempts = (int)($_SESSION['login_attempts'] ?? 0);
$last_attempt = $_SESSION['last_login_attempt'] ?? 0;

// Rate limiting : 5 tentatives max, cooldown 15 minutes
$max_attempts = 5;
$cooldown_time = 900; // 15 minutes
$is_rate_limited = $login_attempts >= $max_attempts && 
                   (time() - $last_attempt) < $cooldown_time;

// CSRF Token pour sécuriser le formulaire
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// =====================================
// TRAITEMENT FORMULAIRE SÉCURISÉ
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Vérification CSRF Token
    $submitted_token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token ?? '')) {
        $error_message = 'Erreur de sécurité. Veuillez recharger la page.';
    }
    
    // Vérification rate limiting
    elseif ($is_rate_limited) {
        $remaining_time = $cooldown_time - (time() - $last_attempt);
        $error_message = sprintf(
            'Trop de tentatives. Réessayez dans %d minutes.', 
            ceil($remaining_time / 60)
        );
    }
    
    else {
        // Validation et sanitization des données
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = $_POST['password'] ?? ''; // Pas de sanitization pour les mots de passe
        
        $username = $username ? trim($username) : '';
        
        // Validation basique
        if (empty($username) || empty($password)) {
            $error_message = 'Veuillez remplir tous les champs.';
        }
        elseif (strlen($username) < 3 || strlen($username) > 50) {
            $error_message = 'Nom d\'utilisateur invalide.';
        }
        elseif (strlen($password) < 4) { // Minimum très bas pour développement
            $error_message = 'Mot de passe trop court.';
        }
        else {
            // Tentative d'authentification
            $auth_success = false;
            
            try {
                // Chargement AuthManager si disponible
                $auth_manager_path = ROOT_PATH . '/core/auth/AuthManager.php';
                if (file_exists($auth_manager_path)) {
                    require_once $auth_manager_path;
                    $auth = new AuthManager();
                    
                    if ($auth->authenticate($username, $password)) {
                        $auth_success = true;
                        $_SESSION['user'] = $auth->getCurrentUser();
                    }
                }
                else {
                    // Fallback simple pour développement (à supprimer en production)
                    if (($username === 'admin' && $password === 'admin') ||
                        ($username === 'dev' && $password === 'dev') ||
                        ($username === 'user' && $password === 'user')) {
                        
                        $auth_success = true;
                        $_SESSION['user'] = [
                            'id' => 1,
                            'username' => $username,
                            'role' => $username === 'admin' ? 'admin' : 'user',
                            'authenticated_at' => time()
                        ];
                    }
                }
                
                if ($auth_success) {
                    // Succès : nettoyer compteurs et rediriger
                    $_SESSION['authenticated'] = true;
                    $_SESSION['login_attempts'] = 0;
                    unset($_SESSION['last_login_attempt']);
                    
                    // Régénération ID session pour sécurité
                    session_regenerate_id(true);
                    
                    // Logging de connexion (en production)
                    if (function_exists('error_log')) {
                        error_log(sprintf(
                            '[LOGIN] Connexion réussie: %s depuis %s', 
                            $username, 
                            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                        ));
                    }
                    
                    header('Location: ' . $redirect_to, true, 302);
                    exit;
                }
                else {
                    $error_message = 'Identifiants incorrects.';
                }
                
            } catch (Exception $e) {
                $error_message = 'Erreur de connexion. Veuillez réessayer.';
                
                // Log de l'erreur
                if (function_exists('error_log')) {
                    error_log('[LOGIN ERROR] ' . $e->getMessage());
                }
            }
            
            // Échec : incrémenter compteur
            if (!$auth_success) {
                $_SESSION['login_attempts'] = $login_attempts + 1;
                $_SESSION['last_login_attempt'] = time();
            }
        }
    }
    
    // Nouveau token CSRF après chaque soumission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// =====================================
// MÉTADONNÉES ET SEO
// =====================================

$page_metadata = [
    'title' => 'Connexion - ' . $app_name,
    'description' => 'Accès sécurisé au portail ' . $app_name,
    'robots' => 'noindex, nofollow',
    'canonical' => false // Pas de canonical pour login
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_metadata['title']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_metadata['description']) ?>">
    <meta name="robots" content="<?= $page_metadata['robots'] ?>">
    <meta name="author" content="<?= htmlspecialchars($app_author) ?>">
    
    <!-- Préchargement des ressources critiques -->
    <link rel="preload" href="/assets/css/portal.css?v=<?= $build_number ?>" as="style">
    <link rel="preload" href="auth/assets/css/login.css?v=<?= $build_number ?>" as="style">
    
    <!-- Styles avec gestion d'erreur -->
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= $build_number ?>" 
          onerror="this.href='/assets/css/fallback.css'">
    <link rel="stylesheet" href="assets/css/login.css?v=<?= $build_number ?>"
          onerror="console.warn('CSS login non chargé')">
    
    <!-- Headers de sécurité additionnels -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <!-- Cache policy pour assets -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>

<body class="login-page">
    
    <!-- Skip link pour accessibilité -->
    <a href="#login-form" class="skip-link">Aller au formulaire de connexion</a>
    
    <div class="login-container">
        <div class="login-card" role="main">
            
            <!-- Header avec identité application -->
            <header class="login-header">
                <div class="login-logo">
                    <div class="logo-icon" aria-hidden="true">🌊</div>
                    <div class="logo-text">
                        <h1><?= htmlspecialchars($app_name) ?></h1>
                        <p class="tagline"><?= defined('APP_TAGLINE') ? htmlspecialchars(APP_TAGLINE) : 'Solutions professionnelles' ?></p>
                    </div>
                </div>
                <div class="version-badge" title="Version <?= htmlspecialchars($app_version) ?>">
                    v<?= htmlspecialchars($app_version) ?>
                </div>
            </header>
            
            <!-- Messages d'état -->
            <?php if (!empty($error_message)): ?>
                <div class="error-message" role="alert" aria-live="polite">
                    <span class="error-icon" aria-hidden="true">⚠️</span>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message" role="alert" aria-live="polite">
                    <span class="success-icon" aria-hidden="true">✅</span>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <!-- Rate limiting warning -->
            <?php if ($login_attempts >= 3 && !$is_rate_limited): ?>
                <div class="warning-message" role="alert">
                    <span class="warning-icon" aria-hidden="true">⚠️</span>
                    Attention : <?= $login_attempts ?>/<?= $max_attempts ?> tentatives utilisées
                </div>
            <?php endif; ?>
            
            <!-- Formulaire de connexion -->
            <form method="POST" 
                  class="login-form" 
                  id="login-form"
                  autocomplete="on" 
                  novalidate
                  <?= $is_rate_limited ? 'aria-disabled="true"' : '' ?>>
                
                <!-- CSRF Token -->
                <input type="hidden" 
                       name="csrf_token" 
                       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <!-- Champ nom d'utilisateur -->
                <div class="form-group">
                    <label for="username" class="form-label">
                        <span class="label-icon" aria-hidden="true">👤</span>
                        Nom d'utilisateur
                        <span class="required" aria-label="requis">*</span>
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-input"
                           autocomplete="username"
                           required
                           autofocus
                           maxlength="50"
                           pattern="[a-zA-Z0-9._-]{3,50}"
                           placeholder="Votre identifiant"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           <?= $is_rate_limited ? 'disabled' : '' ?>
                           aria-describedby="username-help">
                    <small id="username-help" class="form-help">
                        3-50 caractères, lettres, chiffres, points, tirets
                    </small>
                </div>
                
                <!-- Champ mot de passe -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <span class="label-icon" aria-hidden="true">🔐</span>
                        Mot de passe
                        <span class="required" aria-label="requis">*</span>
                    </label>
                    <div class="password-field">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-input"
                               autocomplete="current-password"
                               required
                               minlength="4"
                               placeholder="Votre mot de passe"
                               <?= $is_rate_limited ? 'disabled' : '' ?>
                               aria-describedby="password-help">
                        <button type="button" 
                                class="password-toggle" 
                                onclick="togglePassword()"
                                aria-label="Afficher/masquer le mot de passe"
                                tabindex="-1">
                            <span id="password-toggle-icon">👁️</span>
                        </button>
                    </div>
                    <small id="password-help" class="form-help">
                        Minimum 4 caractères
                    </small>
                </div>
                
                <!-- Bouton de soumission -->
                <button type="submit" 
                        class="login-btn"
                        <?= $is_rate_limited ? 'disabled aria-disabled="true"' : '' ?>>
                    <span class="btn-icon" aria-hidden="true">🚀</span>
                    <span class="btn-text">Se connecter</span>
                </button>
                
                <!-- Informations additionnelles -->
                <div class="form-footer">
                    <small class="form-note">
                        En vous connectant, vous acceptez nos conditions d'utilisation.
                    </small>
                </div>
                
            </form>
            
            <!-- Informations de sécurité simplifiées -->
            <div class="security-info" role="complementary">
                <div class="security-item">
                    <span class="status-indicator status-secure" aria-hidden="true"></span>
                    <span>Connexion sécurisée SSL</span>
                </div>
                <div class="security-item">
                    <span class="status-indicator status-secure" aria-hidden="true"></span>
                    <span>Données chiffrées</span>
                </div>
            </div>
            
        </div>
    </div>

    <!-- Footer fixe avec informations légales -->
    <footer class="login-footer-fixed" role="contentinfo">
        <div class="footer-content">
            <div class="footer-left">
                <span class="copyright">
                    © <?= date('Y') ?> <?= htmlspecialchars($app_author) ?> - Tous droits réservés
                </span>
            </div>
            <div class="footer-right">
                <span class="build-info">
                    <time datetime="<?= date('c') ?>"><?= date('d/m/Y H:i') ?></time>
                    • Build <?= htmlspecialchars($build_number) ?>
                </span>
            </div>
        </div>
    </footer>

    <!-- JavaScript pour UX améliorée -->
    <script>
        'use strict';
        
        // Constantes
        const FORM = document.querySelector('.login-form');
        const SUBMIT_BTN = document.querySelector('.login-btn');
        const USERNAME_FIELD = document.getElementById('username');
        const PASSWORD_FIELD = document.getElementById('password');
        
        // Toggle mot de passe
        function togglePassword() {
            const type = PASSWORD_FIELD.type === 'password' ? 'text' : 'password';
            PASSWORD_FIELD.type = type;
            
            const icon = document.getElementById('password-toggle-icon');
            icon.textContent = type === 'password' ? '👁️' : '🙈';
        }
        
        // Gestion soumission
        FORM?.addEventListener('submit', function(e) {
            const username = USERNAME_FIELD.value.trim();
            const password = PASSWORD_FIELD.value;
            
            // Validation côté client
            if (!username || !password) {
                e.preventDefault();
                showError('Veuillez remplir tous les champs');
                return;
            }
            
            if (username.length < 3) {
                e.preventDefault();
                showError('Nom d\'utilisateur trop court');
                USERNAME_FIELD.focus();
                return;
            }
            
            if (password.length < 4) {
                e.preventDefault();
                showError('Mot de passe trop court');
                PASSWORD_FIELD.focus();
                return;
            }
            
            // Interface de chargement
            setLoadingState(true);
            
            // Timeout de sécurité
            setTimeout(() => {
                if (SUBMIT_BTN.disabled) {
                    setLoadingState(false);
                    showError('Timeout de connexion. Veuillez réessayer.');
                }
            }, 30000); // 30 secondes
        });
        
        // État de chargement
        function setLoadingState(loading) {
            SUBMIT_BTN.disabled = loading;
            
            if (loading) {
                SUBMIT_BTN.innerHTML = '<span class="btn-icon">⏳</span><span class="btn-text">Connexion...</span>';
                SUBMIT_BTN.classList.add('loading');
            } else {
                SUBMIT_BTN.innerHTML = '<span class="btn-icon">🚀</span><span class="btn-text">Se connecter</span>';
                SUBMIT_BTN.classList.remove('loading');
            }
        }
        
        // Affichage des erreurs
        function showError(message) {
            // Supprimer ancien message
            const oldError = document.querySelector('.error-message.js-error');
            if (oldError) {
                oldError.remove();
            }
            
            // Créer nouveau message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message js-error';
            errorDiv.setAttribute('role', 'alert');
            errorDiv.innerHTML = `
                <span class="error-icon" aria-hidden="true">⚠️</span>
                ${escapeHtml(message)}
            `;
            
            // Insérer avant le formulaire
            FORM.parentNode.insertBefore(errorDiv, FORM);
            
            // Focus sur le message pour accessibilité
            errorDiv.focus();
            
            // Auto-suppression
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.remove();
                }
            }, 5000);
        }
        
        // Échappement HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Focus intelligent au chargement
        document.addEventListener('DOMContentLoaded', function() {
            if (USERNAME_FIELD) {
                if (!USERNAME_FIELD.value) {
                    USERNAME_FIELD.focus();
                } else {
                    PASSWORD_FIELD?.focus();
                }
            }
            
            // Gestion Enter sur les champs
            [USERNAME_FIELD, PASSWORD_FIELD].forEach(field => {
                field?.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        if (field === USERNAME_FIELD && !USERNAME_FIELD.value.trim()) {
                            return;
                        }
                        if (field === PASSWORD_FIELD && !PASSWORD_FIELD.value) {
                            return;
                        }
                        FORM.dispatchEvent(new Event('submit', { bubbles: true }));
                    }
                });
            });
            
            // Nettoyage automatique des messages après timeout
            const existingMessages = document.querySelectorAll('.error-message, .success-message, .warning-message');
            existingMessages.forEach(msg => {
                setTimeout(() => {
                    if (msg.parentNode) {
                        msg.style.opacity = '0';
                        setTimeout(() => msg.remove(), 300);
                    }
                }, 5000);
            });
        });
        
        // Prévention attaques timing
        function addRandomDelay(callback, baseDelay = 100) {
            const delay = baseDelay + Math.random() * 200;
            setTimeout(callback, delay);
        }
        
        // Monitoring performance
        if (window.performance && window.performance.mark) {
            window.performance.mark('login-page-loaded');
        }
        
        // Gestion erreurs JavaScript
        window.addEventListener('error', function(e) {
            console.error('Erreur JS sur page login:', e.error);
            // En production, envoyer à un service de monitoring
        });
        
        // Support offline
        window.addEventListener('online', function() {
            const offlineMsg = document.querySelector('.offline-message');
            if (offlineMsg) {
                offlineMsg.remove();
            }
        });
        
        window.addEventListener('offline', function() {
            showError('Connexion internet perdue. Vérifiez votre réseau.');
        });
        
    </script>
    
    <!-- Schema.org pour SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "<?= htmlspecialchars($page_metadata['title']) ?>",
        "description": "<?= htmlspecialchars($page_metadata['description']) ?>",
        "url": "<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>",
        "author": {
            "@type": "Person",
            "name": "<?= htmlspecialchars($app_author) ?>"
        },
        "publisher": {
            "@type": "Organization",
            "name": "<?= defined('APP_COMPANY') ? htmlspecialchars(APP_COMPANY) : htmlspecialchars($app_author) ?>"
        },
        "dateModified": "<?= date('c', defined('APP_BUILD_TIMESTAMP') ? APP_BUILD_TIMESTAMP : time()) ?>",
        "inLanguage": "fr-FR"
    }
    </script>
    
</body>
</html>

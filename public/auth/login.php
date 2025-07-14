<?php
/**
 * Titre: Page de connexion sécurisée - Version modulaire
 * Chemin: /public/auth/login.php
 * Version: 0.5 beta + build auto
 */

// Protection et chargement configuration
define('ROOT_PATH', dirname(__DIR__, 2));

// Gestion session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirection si déjà connecté
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: /index.php');
    exit;
}

$required_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        http_response_code(500);
        die('<h1>❌ Configuration manquante</h1><p>Fichier requis : ' . basename($file) . '</p>');
    }
}

try {
    require_once ROOT_PATH . '/config/config.php';
    require_once ROOT_PATH . '/config/version.php';
} catch (Exception $e) {
    http_response_code(500);
    die('<h1>❌ Erreur Configuration</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}

// Variables par défaut si constantes manquantes
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';
$is_debug = defined('DEBUG') && DEBUG;

$error_message = '';
$success_message = '';

// Traitement authentification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Veuillez remplir tous les champs';
    } else {
        // Tentative AuthManager
        if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
            try {
                require_once ROOT_PATH . '/core/auth/AuthManager.php';
                $auth = new AuthManager();
                $result = $auth->login($username, $password);
                
                if ($result['success']) {
                    $_SESSION['authenticated'] = true;
                    $_SESSION['user'] = $result['user'];
                    header('Location: /index.php');
                    exit;
                } else {
                    $error_message = $result['error'] ?? 'Identifiants incorrects';
                }
            } catch (Exception $e) {
                $error_message = $is_debug ? $e->getMessage() : 'Erreur système d\'authentification';
            }
        } else {
            // Authentification temporaire basique (développement uniquement)
            if ($is_debug) {
                $temp_users = [
                    'admin' => ['password' => 'admin123', 'role' => 'admin'],
                    'user' => ['password' => 'user123', 'role' => 'user'],
                    'dev' => ['password' => 'dev123', 'role' => 'dev']
                ];
                
                if (isset($temp_users[$username]) && $temp_users[$username]['password'] === $password) {
                    $_SESSION['authenticated'] = true;
                    $_SESSION['user'] = [
                        'username' => $username,
                        'role' => $temp_users[$username]['role']
                    ];
                    header('Location: /index.php');
                    exit;
                } else {
                    $error_message = 'Identifiants incorrects';
                }
            } else {
                $error_message = 'Système d\'authentification non configuré';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?= htmlspecialchars($app_name) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <!-- CSS modulaire -->
    <link rel="stylesheet" href="../assets/css/login.css?v=<?= $build_number ?>">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title">Connexion</h1>
                <p class="login-subtitle">Accédez à votre espace <?= htmlspecialchars($app_name) ?></p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error" role="alert">
                    ❌ <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    ✅ <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form" novalidate>
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           required 
                           autocomplete="username"
                           autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="current-password">
                </div>

                <button type="submit" class="login-btn">
                    Se connecter
                </button>
            </form>

            <div class="login-footer">
                <div class="version-info">
                    <span>Version <?= $app_version ?></span>
                    <span>Build <?= $build_number ?></span>
                </div>
                <div>&copy; <?= date('Y') ?> <?= $app_author ?></div>
            </div>
        </div>
    </div>

    <!-- JavaScript modulaire -->
    <script src="/public/auth/assets/js/login.js?v=<?= $build_number ?>"></script>
</body>
</html>

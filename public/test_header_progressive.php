<?php
/**
 * Test progressif du header en reproduisant les conditions exactes
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo '<h1>🔍 TEST PROGRESSIF HEADER</h1>';

// === SIMULATION EXACTE DU HEADER ===

echo '<h2>Étape 1: Protection ROOT_PATH</h2>';
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
    echo '<p>✅ ROOT_PATH défini: ' . ROOT_PATH . '</p>';
} else {
    echo '<p>✅ ROOT_PATH déjà défini</p>';
}

echo '<h2>Étape 2: Chargement config.php</h2>';
try {
    require_once ROOT_PATH . '/config/config.php';
    echo '<p>✅ config.php chargé</p>';
} catch (Exception $e) {
    echo '<p>❌ Erreur config.php: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

echo '<h2>Étape 3: Chargement version.php</h2>';
try {
    require_once ROOT_PATH . '/config/version.php';
    echo '<p>✅ version.php chargé</p>';
} catch (Exception $e) {
    echo '<p>❌ Erreur version.php: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

echo '<h2>Étape 4: Chargement roles.php (LIGNE 13 DU HEADER)</h2>';
try {
    require_once ROOT_PATH . '/config/roles.php';
    echo '<p>✅ roles.php chargé dans le contexte complet</p>';
} catch (Exception $e) {
    echo '<p>❌ ERREUR roles.php dans contexte: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>📍 Ligne: ' . $e->getLine() . '</p>';
    echo '<p>📁 Fichier: ' . htmlspecialchars($e->getFile()) . '</p>';
    exit;
} catch (Error $e) {
    echo '<p>❌ ERREUR FATALE roles.php: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>📍 Ligne: ' . $e->getLine() . '</p>';
    exit;
}

echo '<h2>Étape 5: Chargement debug.php (LIGNE 16-18)</h2>';
if (file_exists(ROOT_PATH . '/config/debug.php')) {
    try {
        require_once ROOT_PATH . '/config/debug.php';
        echo '<p>✅ debug.php chargé</p>';
    } catch (Exception $e) {
        echo '<p>❌ Erreur debug.php: ' . htmlspecialchars($e->getMessage()) . '</p>';
        exit;
    }
} else {
    echo '<p>ℹ️ debug.php absent (normal)</p>';
}

echo '<h2>Étape 6: Initialisation variables (LIGNES 20-22)</h2>';
try {
    $user_authenticated = false;
    $current_user = null;
    echo '<p>✅ Variables initialisées</p>';
} catch (Exception $e) {
    echo '<p>❌ Erreur variables: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

echo '<h2>Étape 7: Vérification AuthManager (LIGNES 25-45)</h2>';
try {
    // Simulation de la logique d'authentification du header
    if (file_exists(ROOT_PATH . '/core/auth/AuthManager.php')) {
        require_once ROOT_PATH . '/core/auth/AuthManager.php';
        echo '<p>✅ AuthManager trouvé et chargé</p>';
        
        // Test instanciation
        $auth = new AuthManager();
        echo '<p>✅ AuthManager instancié</p>';
        
        // Test méthodes
        if (method_exists($auth, 'isAuthenticated')) {
            $is_auth = $auth->isAuthenticated();
            echo '<p>✅ isAuthenticated() appelé: ' . ($is_auth ? 'true' : 'false') . '</p>';
        } else {
            echo '<p>⚠️ Méthode isAuthenticated() manquante</p>';
        }
        
    } else {
        echo '<p>ℹ️ AuthManager absent - utilisation fallback session</p>';
        
        // Fallback session comme dans le header
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            echo '<p>✅ Session démarrée</p>';
        } else {
            echo '<p>✅ Session déjà active</p>';
        }
        
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            $user_authenticated = true;
            $current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
            echo '<p>✅ Utilisateur authentifié via session</p>';
        } else {
            echo '<p>ℹ️ Utilisateur non authentifié</p>';
        }
    }
} catch (Exception $e) {
    echo '<p>❌ ERREUR Authentification: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>📍 Ligne: ' . $e->getLine() . '</p>';
    echo '<p>🎯 CETTE ERREUR PEUT CAUSER L\'ERREUR 500 !</p>';
    exit;
} catch (Error $e) {
    echo '<p>❌ ERREUR FATALE Authentification: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>📍 Ligne: ' . $e->getLine() . '</p>';
    echo '<p>🎯 CETTE ERREUR CAUSE L\'ERREUR 500 !</p>';
    exit;
}

echo '<h2>Étape 8: Variables avec fallbacks (LIGNES 50-60)</h2>';
try {
    $page_title = htmlspecialchars($page_title ?? 'Portail Guldagil');
    $page_subtitle = htmlspecialchars($page_subtitle ?? 'Solutions professionnelles');
    $page_description = htmlspecialchars($page_description ?? 'Portail de gestion');
    $current_module = htmlspecialchars($current_module ?? 'home');
    
    $app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
    $build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : date('Ymd') . '001';
    $app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
    $app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';
    
    echo '<p>✅ Variables avec fallbacks OK</p>';
} catch (Exception $e) {
    echo '<p>❌ Erreur variables fallbacks: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

echo '<h2>Étape 9: Configuration modules (LIGNES 62-75)</h2>';
try {
    $all_modules = [
        'home' => ['icon' => '🏠', 'color' => '#3182ce', 'status' => 'active', 'name' => 'Accueil', 'routes' => ['', 'home']],
        'port' => ['icon' => '📦', 'color' => '#059669', 'status' => 'active', 'name' => 'Frais de port', 'routes' => ['port', 'calculateur']],
        'adr' => ['icon' => '⚠️', 'color' => '#dc2626', 'status' => 'active', 'name' => 'ADR', 'routes' => ['adr']],
        'user' => ['icon' => '👤', 'color' => '#7c2d12', 'status' => 'active', 'name' => 'Mon compte', 'routes' => ['user', 'profile']],
        'admin' => ['icon' => '⚙️', 'color' => '#1f2937', 'status' => 'active', 'name' => 'Administration', 'routes' => ['admin']],
    ];
    
    echo '<p>✅ Configuration modules OK</p>';
} catch (Exception $e) {
    echo '<p>❌ Erreur configuration modules: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

echo '<h2>Étape 10: Navigation modules - LIGNE CRITIQUE 111</h2>';
try {
    $navigation_modules = [];
    if ($user_authenticated) {
        $user_role = $current_user['role'] ?? 'user';
        echo "<p>Appel getNavigationModules avec role='$user_role'...</p>";
        $navigation_modules = getNavigationModules($user_role, $all_modules);
        echo '<p>✅ getNavigationModules() réussi - ' . count($navigation_modules) . ' modules</p>';
    } else {
        echo '<p>ℹ️ Utilisateur non authentifié - pas de navigation modules</p>';
    }
} catch (Exception $e) {
    echo '<p>❌ ERREUR CRITIQUE getNavigationModules: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>📍 Ligne: ' . $e->getLine() . '</p>';
    echo '<p>🎯 CETTE ERREUR CAUSE L\'ERREUR 500 !</p>';
    exit;
} catch (Error $e) {
    echo '<p>❌ ERREUR FATALE getNavigationModules: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>📍 Ligne: ' . $e->getLine() . '</p>';
    echo '<p>🎯 CETTE ERREUR CAUSE L\'ERREUR 500 !</p>';
    exit;
}

echo '<hr>';
echo '<p><strong>✅ TOUS LES TESTS PASSÉS !</strong></p>';
echo '<p>Si ce test fonctionne mais que le header ne fonctionne pas, il y a un problème spécifique au contexte du header.</p>';
?>

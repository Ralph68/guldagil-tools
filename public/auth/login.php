<?php
/**
 * Titre: Page de connexion par email - Version améliorée
 * Chemin: /auth/login.php
 * Version: 0.5 beta + build auto
 */

session_start();

// Redirection si déjà connecté
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    $redirect = $_GET['redirect'] ?? '/';
    header('Location: ' . $redirect);
    exit;
}

// Configuration et sécurité
define('ROOT_PATH', dirname(__DIR__));

// Chargement configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        http_response_code(500);
        die('<h1>❌ Configuration manquante</h1><p>Fichier requis : ' . basename($file) . '</p>');
    }
    require_once $file;
}

// Variables par défaut avec fallbacks sécurisés
$app_name = defined('APP_NAME') ? APP_NAME : 'Portail Guldagil';
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';
$app_author = defined('APP_AUTHOR') ? APP_AUTHOR : 'Jean-Thomas RUNSER';
$is_debug = defined('DEBUG') && DEBUG;

// Messages et état
$error_message = '';
$success_message = '';
$login_attempts = $_SESSION['login_attempts'] ?? 0;
$last_attempt = $_SESSION['last_attempt'] ?? 0;
$is_locked = false;

// Protection contre brute force
$lockout_time = 900; // 15 minutes
$max_attempts = 5;

if ($login_attempts >= $max_attempts && (time() - $last_attempt) < $lockout_time) {
    $is_locked = true;
    $remaining_time = $lockout_time - (time() - $last_attempt);
}

// Messages d'information selon contexte
$redirect_message = '';
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'disconnected':
            $success_message = 'Vous avez été déconnecté avec succès';
            break;
        case 'timeout':
            $error_message = 'Votre session a expiré, veuillez vous reconnecter';
            break;
        case 'required':
            $error_message = 'Vous devez être connecté pour accéder à cette page';
            break;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $email_or_username = trim($_POST['email_or_username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($email_or_username) || empty($password)) {
        $error_message = 'Veuillez remplir tous les champs';
    } else {
        // Tentative d'authentification
        $auth_result = authenticateUser($email_or_username, $password, $remember_me);
        
        if ($auth_result['success']) {
            // Connexion réussie
            $_SESSION['authenticated'] = true;
            $_SESSION['user'] = $auth_result['user'];
            $_SESSION['login_time'] = time();
            
            // Reset tentatives
            unset($_SESSION['login_attempts']);
            unset($_SESSION['last_attempt']);
            
            // Log de connexion
            logAuthEvent('LOGIN_SUCCESS', $auth_result['user']['email'], [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            // Cookie Remember Me
            if ($remember_me) {
                setRememberMeCookie($auth_result['user']['id']);
            }
            
            // Redirection
            $redirect = $_GET['redirect'] ?? '/';
            header('Location: ' . $redirect);
            exit;
            
        } else {
            // Échec de connexion
            $error_message = $auth_result['error'];
            $_SESSION['login_attempts'] = $login_attempts + 1;
            $_SESSION['last_attempt'] = time();
            
            // Log de tentative échouée
            logAuthEvent('LOGIN_FAILED', $email_or_username, [
                'error' => $auth_result['error'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'attempts' => $_SESSION['login_attempts']
            ]);
            
            // Vérifier si compte bloqué maintenant
            if ($_SESSION['login_attempts'] >= $max_attempts) {
                $is_locked = true;
                $remaining_time = $lockout_time;
                $error_message = "Trop de tentatives échouées. Compte temporairement bloqué.";
            }
        }
    }
}

/**
 * Authentification utilisateur avec support email et base EPI
 */
function authenticateUser($email_or_username, $password, $remember_me = false) {
    try {
        // Connexion à la base de données
        if (function_exists('getDB')) {
            $db = getDB();
        } else {
            throw new Exception("Base de données non disponible");
        }
        
        // 1. Vérifier d'abord dans la table auth_users (système principal)
        $auth_user = checkAuthUsers($db, $email_or_username, $password);
        if ($auth_user) {
            return ['success' => true, 'user' => $auth_user];
        }
        
        // 2. Vérifier dans les employés EPI (fallback)
        $epi_user = checkEPIEmployees($db, $email_or_username, $password);
        if ($epi_user) {
            return ['success' => true, 'user' => $epi_user];
        }
        
        // 3. Utilisateurs de test en mode debug
        if (defined('DEBUG') && DEBUG) {
            $test_user = checkTestUsers($email_or_username, $password);
            if ($test_user) {
                return ['success' => true, 'user' => $test_user];
            }
        }
        
        return ['success' => false, 'error' => 'Email/nom d\'utilisateur ou mot de passe incorrect'];
        
    } catch (Exception $e) {
        error_log("Erreur authentification: " . $e->getMessage());
        return ['success' => false, 'error' => 'Erreur système d\'authentification'];
    }
}

/**
 * Vérification dans la table auth_users principale
 */
function checkAuthUsers($db, $email_or_username, $password) {
    try {
        // Préparer requête pour email OU username
        $sql = "SELECT * FROM auth_users WHERE (email = ? OR username = ?) AND is_active = 1 LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$email_or_username, $email_or_username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Mettre à jour dernière connexion
            $update_stmt = $db->prepare("UPDATE auth_users SET last_login = NOW() WHERE id = ?");
            $update_stmt->execute([$user['id']]);
            
            return [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'] ?? $user['username'] . '@guldagil.com',
                'role' => $user['role'],
                'source' => 'auth_users'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Erreur checkAuthUsers: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Vérification dans les employés EPI comme fallback
 */
function checkEPIEmployees($db, $email_or_username, $password) {
    try {
        // Requête adaptée à votre structure EPI
        $sql = "SELECT e.*, d.name as department_name 
                FROM epi_employees e 
                LEFT JOIN epi_departments d ON e.department_id = d.id 
                WHERE (e.email = ? OR e.username = ?) AND e.is_active = 1 
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$email_or_username, $email_or_username]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($employee) {
            // Vérification mot de passe (adapté selon votre système EPI)
            $password_valid = false;
            
            if (isset($employee['password']) && !empty($employee['password'])) {
                // Si hash moderne
                if (password_verify($password, $employee['password'])) {
                    $password_valid = true;
                }
                // Si hash MD5 legacy (à migrer progressivement)
                elseif (md5($password) === $employee['password']) {
                    $password_valid = true;
                    // Opportunité de mise à jour vers hash moderne
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $db->prepare("UPDATE epi_employees SET password = ? WHERE id = ?");
                    $update_stmt->execute([$new_hash, $employee['id']]);
                }
            }
            // Mot de passe temporaire basé sur matricule (pour migration)
            elseif (!empty($employee['employee_id']) && $password === 'gul' . $employee['employee_id']) {
                $password_valid = true;
            }
            
            if ($password_valid) {
                // Déterminer le rôle basé sur le département ou le poste
                $role = determineUserRole($employee);
                
                return [
                    'id' => 'epi_' . $employee['id'],
                    'username' => $employee['username'] ?? $employee['first_name'] . '.' . $employee['last_name'],
                    'email' => $employee['email'],
                    'full_name' => $employee['first_name'] . ' ' . $employee['last_name'],
                    'role' => $role,
                    'department' => $employee['department_name'] ?? 'Non défini',
                    'employee_id' => $employee['employee_id'] ?? null,
                    'source' => 'epi_employees'
                ];
            }
        }
        
    } catch (Exception $e) {
        error_log("Erreur checkEPIEmployees: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Déterminer le rôle utilisateur basé sur les données EPI
 */
function determineUserRole($employee) {
    // Rôles admin pour certains départements
    $admin_departments = ['IT', 'INFORMATIQUE', 'DIRECTION', 'RH'];
    $admin_positions = ['RESPONSABLE', 'CHEF', 'DIRECTEUR', 'MANAGER'];
    
    $department = strtoupper($employee['department_name'] ?? '');
    $position = strtoupper($employee['position'] ?? $employee['job_title'] ?? '');
    
    // Développeur/IT
    if (in_array($department, ['IT', 'INFORMATIQUE']) || 
        strpos($position, 'DEVELOPPEUR') !== false ||
        strpos($position, 'INFORMATIQUE') !== false) {
        return 'dev';
    }
    
    // Admin
    if (in_array($department, $admin_departments)) {
        return 'admin';
    }
    
    foreach ($admin_positions as $admin_pos) {
        if (strpos($position, $admin_pos) !== false) {
            return 'admin';
        }
    }
    
    // Utilisateur standard
    return 'user';
}

/**
 * Utilisateurs de test pour développement
 */
function checkTestUsers($email_or_username, $password) {
    $test_users = [
        'dev@guldagil.com' => [
            'password' => 'DevGul2024!',
            'user' => [
                'id' => 'test_dev',
                'username' => 'dev',
                'email' => 'dev@guldagil.com',
                'role' => 'dev',
                'source' => 'test'
            ]
        ],
        'admin@guldagil.com' => [
            'password' => 'AdminGul2024!',
            'user' => [
                'id' => 'test_admin',
                'username' => 'admin',
                'email' => 'admin@guldagil.com',
                'role' => 'admin',
                'source' => 'test'
            ]
        ],
        'user@guldagil.com' => [
            'password' => 'UserGul2024!',
            'user' => [
                'id' => 'test_user',
                'username' => 'user',
                'email' => 'user@guldagil.com',
                'role' => 'user',
                'source' => 'test'
            ]
        ]
    ];
    
    // Support aussi par username
    $username_map = [
        'dev' => 'dev@guldagil.com',
        'admin' => 'admin@guldagil.com',
        'user' => 'user@guldagil.com'
    ];
    
    $lookup_key = $username_map[$email_or_username] ?? $email_or_username;
    
    if (isset($test_users[$lookup_key]) && $test_users[$lookup_key]['password'] === $password) {
        return $test_users[$lookup_key]['user'];
    }
    
    return false;
}

/**
 * Cookie Remember Me sécurisé
 */
function setRememberMeCookie($user_id) {
    $token = bin2hex(random_bytes(32));
    $expires = time() + (30 * 24 * 60 * 60); // 30 jours
    
    // Stocker en base (à implémenter)
    // INSERT INTO auth_remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)
    
    setcookie('remember_token', $token, $expires, '/', '', true, true);
}

/**
 * Log des événements d'authentification
 */
function logAuthEvent($event, $identifier, $data = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'identifier' => $identifier,
        'data' => $data
    ];
    
    $log_dir = ROOT_PATH . '/storage/logs';
    if (is_dir($log_dir) || mkdir($log_dir, 0755, true)) {
        file_put_contents($log_dir . '/auth.log', json_encode($log_entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
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
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    
    <style>
        /* Variables CSS */
        :root {
            --primary-blue: #3182ce;
            --primary-blue-dark: #2c5282;
            --primary-blue-light: #63b3ed;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --green-100: #dcfce7;
            --green-500: #22c55e;
            --red-100: #fee2e2;
            --red-500: #ef4444;
            --yellow-100: #fef3c7;
            --yellow-500: #eab308;
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            --radius-sm: 0.25rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --transition-fast: 0.15s ease;
            --transition-normal: 0.3s ease;
        }
        
        /* Reset et base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--gray-900);
            background: linear-gradient(135deg, var(--gray-50), var(--gray-100));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-lg);
        }
        
        /* Container principal */
        .login-container {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 600px;
        }
        
        /* Section branding */
        .login-branding {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
            color: white;
            padding: var(--spacing-2xl);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-branding::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(3deg); }
        }
        
        .branding-logo {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: var(--spacing-lg);
            border: 3px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .branding-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
            position: relative;
            z-index: 1;
        }
        
        .branding-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: var(--spacing-lg);
            position: relative;
            z-index: 1;
        }
        
        .branding-features {
            list-style: none;
            text-align: left;
            position: relative;
            z-index: 1;
        }
        
        .branding-features li {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-sm);
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        /* Section formulaire */
        .login-form-section {
            padding: var(--spacing-2xl);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }
        
        .form-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--spacing-sm);
        }
        
        .form-subtitle {
            color: var(--gray-600);
            font-size: 0.875rem;
        }
        
        /* Messages d'alerte */
        .alert {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: var(--green-100);
            color: #065f46;
            border: 1px solid var(--green-500);
        }
        
        .alert-error {
            background: var(--red-100);
            color: #7f1d1d;
            border: 1px solid var(--red-500);
        }
        
        .alert-warning {
            background: var(--yellow-100);
            color: #92400e;
            border: 1px solid var(--yellow-500);
        }
        
        .alert-icon {
            font-size: 1rem;
        }
        
        /* Formulaire */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--gray-700);
            font-size: 0.875rem;
        }
        
        .form-input {
            padding: var(--spacing-md);
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: var(--transition-fast);
            background: white;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-input:disabled {
            background: var(--gray-100);
            color: var(--gray-500);
            cursor: not-allowed;
        }
        
        .form-help {
            font-size: 0.75rem;
            color: var(--gray-500);
        }
        
        /* Options avancées */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--spacing-md);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .checkbox-label {
            font-size: 0.875rem;
            color: var(--gray-600);
            cursor: pointer;
        }
        
        .forgot-password {
            color: var(--primary-blue);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: var(--transition-fast);
        }
        
        .forgot-password:hover {
            color: var(--primary-blue-dark);
            text-decoration: underline;
        }
        
        /* Bouton de connexion */
        .submit-button {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
            color: white;
            border: none;
            padding: var(--spacing-md) var(--spacing-xl);
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
        }
        
        .submit-button:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }
        
        .submit-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .submit-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .submit-button:hover::before {
            left: 100%;
        }
        
        /* Compte bloqué */
        .lockout-info {
            text-align: center;
            padding: var(--spacing-lg);
            background: var(--red-100);
            border: 1px solid var(--red-500);
            border-radius: var(--radius-md);
            color: #7f1d1d;
        }
        
        .lockout-timer {
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: var(--spacing-sm);
        }
        
        /* Informations de test */
        .debug-info {
            margin-top: var(--spacing-xl);
            padding: var(--spacing-md);
            background: var(--gray-100);
            border-radius: var(--radius-md);
            border: 1px dashed var(--gray-300);
        }
        
        .debug-title {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: var(--spacing-sm);
            font-size: 0.875rem;
        }
        
        .debug-accounts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: var(--spacing-sm);
        }
        
        .debug-account {
            background: white;
            padding: var(--spacing-sm);
            border-radius: var(--radius-sm);
            border: 1px solid var(--gray-200);
            cursor: pointer;
            transition: var(--transition-fast);
        }
        
        .debug-account:hover {
            border-color: var(--primary-blue);
            background: rgba(59, 130, 246, 0.05);
        }
        
        .debug-account strong {
            display: block;
            font-size: 0.75rem;
            color: var(--gray-900);
        }
        
        .debug-account span {
            font-size: 0.625rem;
            color: var(--gray-500);
        }
        
        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: var(--spacing-xl);
            padding-top: var(--spacing-lg);
            border-top: 1px solid var(--gray-200);
            color: var(--gray-500);
            font-size: 0.75rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 400px;
                min-height: auto;
            }
            
            .login-branding {
                padding: var(--spacing-xl);
                min-height: 200px;
            }
            
            .branding-logo {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }
            
            .branding-title {
                font-size: 1.5rem;
            }
            
            .login-form-section {
                padding: var(--spacing-xl);
            }
            
            .form-options {
                flex-direction: column;
                align-items: stretch;
                gap: var(--spacing-sm);
            }
            
            .debug-accounts {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: var(--spacing-sm);
            }
            
            .login-container {
                max-width: 100%;
            }
            
            .login-branding,
            .login-form-section {
                padding: var(--spacing-lg);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Section Branding -->
        <div class="login-branding">
            <div class="branding-logo">
                🌊
            </div>
            <h1 class="branding-title">Guldagil</h1>
            <p class="branding-subtitle">Solutions professionnelles</p>
            
            <ul class="branding-features">
                <li><span>🚛</span> Calculateur de frais de port</li>
                <li><span>⚠️</span> Gestion ADR</li>
                <li><span>✅</span> Contrôle qualité</li>
                <li><span>🛡️</span> Gestion EPI</li>
            </ul>
        </div>
        
        <!-- Section Formulaire -->
        <div class="login-form-section">
            <div class="form-header">
                <h2 class="form-title">Connexion</h2>
                <p class="form-subtitle">Accédez à votre espace de travail</p>
            </div>
            
            <!-- Messages d'alerte -->
            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                <span><?= htmlspecialchars($success_message) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <span class="alert-icon">❌</span>
                <span><?= htmlspecialchars($error_message) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($is_locked): ?>
            <div class="lockout-info">
                <div class="lockout-timer" id="lockoutTimer">
                    Compte bloqué - Temps restant: <span id="remainingTime"><?= gmdate("i:s", $remaining_time) ?></span>
                </div>
                <p>Trop de tentatives de connexion échouées. Veuillez patienter avant de réessayer.</p>
            </div>
            <?php else: ?>
            
            <!-- Formulaire de connexion -->
            <form method="POST" class="login-form" id="loginForm">
                <div class="form-group">
                    <label for="email_or_username" class="form-label">Email ou nom d'utilisateur</label>
                    <input 
                        type="text" 
                        id="email_or_username" 
                        name="email_or_username" 
                        class="form-input" 
                        placeholder="votre.email@guldagil.com"
                        value="<?= htmlspecialchars($_POST['email_or_username'] ?? '') ?>"
                        required
                        autocomplete="username"
                        autofocus>
                    <div class="form-help">Utilisez votre adresse email professionnelle ou votre nom d'utilisateur</div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="••••••••"
                        required
                        autocomplete="current-password">
                </div>
                
                <div class="form-options">
                    <div class="checkbox-group">
                        <input type="checkbox" id="remember_me" name="remember_me">
                        <label for="remember_me" class="checkbox-label">Se souvenir de moi</label>
                    </div>
                    <a href="/auth/forgot-password.php" class="forgot-password">Mot de passe oublié ?</a>
                </div>
                
                <button type="submit" class="submit-button">
                    Se connecter
                </button>
            </form>
            
            <?php endif; ?>
            
            <!-- Informations de test en mode debug -->
            <?php if ($is_debug): ?>
            <div class="debug-info">
                <div class="debug-title">🔧 Comptes de test (Mode développement)</div>
                <div class="debug-accounts">
                    <div class="debug-account" onclick="fillLogin('dev@guldagil.com', 'DevGul2024!')">
                        <strong>Développeur</strong>
                        <span>dev@guldagil.com</span>
                    </div>
                    <div class="debug-account" onclick="fillLogin('admin@guldagil.com', 'AdminGul2024!')">
                        <strong>Administrateur</strong>
                        <span>admin@guldagil.com</span>
                    </div>
                    <div class="debug-account" onclick="fillLogin('user@guldagil.com', 'UserGul2024!')">
                        <strong>Utilisateur</strong>
                        <span>user@guldagil.com</span>
                    </div>
                </div>
                <div style="margin-top: var(--spacing-sm); font-size: 0.75rem; color: var(--gray-600);">
                    💡 Support aussi : employés EPI avec email + mot de passe temporaire "gul[matricule]"
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Footer -->
            <div class="login-footer">
                <p>Portail Guldagil v<?= htmlspecialchars($app_version) ?> - Build #<?= substr($build_number, -8) ?></p>
                <p>© <?= date('Y') ?> <?= htmlspecialchars($app_author) ?> - Solutions professionnelles</p>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Compte à rebours pour déblocage
        <?php if ($is_locked): ?>
        let remainingTime = <?= $remaining_time ?>;
        
        function updateTimer() {
            const minutes = Math.floor(remainingTime / 60);
            const seconds = remainingTime % 60;
            document.getElementById('remainingTime').textContent = 
                String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            
            if (remainingTime <= 0) {
                location.reload();
            } else {
                remainingTime--;
            }
        }
        
        setInterval(updateTimer, 1000);
        <?php endif; ?>
        
        // Remplissage automatique pour les comptes de test
        function fillLogin(email, password) {
            document.getElementById('email_or_username').value = email;
            document.getElementById('password').value = password;
            document.getElementById('email_or_username').focus();
        }
        
        // Validation côté client
        document.getElementById('loginForm')?.addEventListener('submit', function(e) {
            const email = document.getElementById('email_or_username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs');
                return false;
            }
            
            // Validation email basique
            if (email.includes('@') && !isValidEmail(email)) {
                e.preventDefault();
                alert('Veuillez saisir une adresse email valide');
                return false;
            }
            
            return true;
        });
        
        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
        
        // Auto-focus sur le champ password si email déjà rempli
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email_or_username');
            const passwordField = document.getElementById('password');
            
            if (emailField.value.trim() !== '') {
                passwordField.focus();
            }
        });
        
        // Gestion des touches spéciales
        document.addEventListener('keydown', function(e) {
            // Ctrl+Shift+D pour activer le mode debug (si pas déjà activé)
            if (e.ctrlKey && e.shiftKey && e.code === 'KeyD' && !<?= $is_debug ? 'true' : 'false' ?>) {
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('debug', '1');
                window.location.search = urlParams;
            }
        });
    </script>
</body>
</html>

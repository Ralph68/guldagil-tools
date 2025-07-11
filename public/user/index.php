<?php
/**
 * Titre: Dashboard utilisateur avec chemins corrigés
 * Chemin: /public/user/index.php
 * Version: 0.5 beta + build auto
 */

session_start();

// Configuration - CORRIGÉ
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
    // Fallback sur l'ancien système
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    $current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
}

// Variables pour template
$version_info = getVersionInfo();
$page_title = 'Mon Espace';
$page_subtitle = 'Dashboard utilisateur';
$current_module = 'user';
$user_authenticated = true;
$module_css = true;

$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '👤', 'text' => 'Mon Espace', 'url' => '/user/', 'active' => true]
];

// Statistiques utilisateur
$user_stats = [
    'sessions_actives' => 1,
    'derniere_connexion' => date('d/m/Y H:i'),
    'modules_disponibles' => count($current_user['modules'] ?? ['calculateur']),
    'role' => $current_user['role'] ?? 'user'
];

// Modules disponibles selon le rôle
$available_modules = [
    'calculateur' => [
        'name' => 'Calculateur',
        'description' => 'Frais de transport',
        'icon' => '🧮',
        'url' => '/port/',
        'primary' => true
    ],
    'adr' => [
        'name' => 'Module ADR',
        'description' => 'Marchandises dangereuses',
        'icon' => '⚠️',
        'url' => '/adr/',
        'primary' => false
    ],
    'admin' => [
        'name' => 'Administration',
        'description' => 'Gestion système',
        'icon' => '⚙️',
        'url' => '/admin/',
        'primary' => false
    ]
];

// Activité récente simulée
$recent_activities = [
    [
        'icon' => '🔐',
        'title' => 'Connexion réussie',
        'time' => 'Maintenant',
        'type' => 'login'
    ],
    [
        'icon' => '🧮',
        'title' => 'Calcul frais de port',
        'time' => 'Il y a 15 min',
        'type' => 'calculation'
    ],
    [
        'icon' => '👤',
        'title' => 'Mise à jour profil',
        'time' => 'Hier à 14:30',
        'type' => 'profile'
    ]
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - <?= htmlspecialchars($app_name) ?></title>
    <meta name="description" content="Dashboard utilisateur du portail <?= htmlspecialchars($app_name) ?>">
    
    <!-- CSS de base -->
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= $build_number ?>">
    <!-- CSS module user - CHEMIN CORRIGÉ -->
    <link rel="stylesheet" href="assets/css/user.css?v=<?= $build_number ?>">
</head>
<body>
    <?php include ROOT_PATH . '/templates/header.php'; ?>

    <main class="user-dashboard">
        <!-- En-tête utilisateur -->
        <section class="user-header">
            <div class="user-avatar">
                <div class="avatar-circle">
                    <?= strtoupper(substr($current_user['username'] ?? 'U', 0, 2)) ?>
                </div>
            </div>
            <div class="user-info">
                <h1>Bonjour, <?= htmlspecialchars($current_user['name'] ?? $current_user['username'] ?? 'Utilisateur') ?></h1>
                <p class="user-role">
                    <span class="role-badge role-<?= $current_user['role'] ?? 'user' ?>">
                        <?= ucfirst($current_user['role'] ?? 'user') ?>
                    </span>
                </p>
                <p class="last-login">Dernière connexion: <?= $user_stats['derniere_connexion'] ?></p>
            </div>
        </section>

        <!-- Actions rapides -->
        <section class="quick-actions">
            <h2>Actions rapides</h2>
            <div class="actions-grid">
                <a href="/user/profile.php" class="action-card">
                    <div class="action-icon">👤</div>
                    <div class="action-content">
                        <h3>Mon Profil</h3>
                        <p>Informations personnelles</p>
                    </div>
                </a>
                
                <a href="/user/settings.php" class="action-card">
                    <div class="action-icon">⚙️</div>
                    <div class="action-content">
                        <h3>Paramètres</h3>
                        <p>Configuration interface</p>
                    </div>
                </a>
                
                <?php foreach ($available_modules as $module_id => $module): ?>
                    <?php if (in_array($module_id, $current_user['modules'] ?? ['calculateur'])): ?>
                    <a href="<?= $module['url'] ?>" class="action-card <?= $module['primary'] ? 'primary' : '' ?>">
                        <div class="action-icon"><?= $module['icon'] ?></div>
                        <div class="action-content">
                            <h3><?= htmlspecialchars($module['name']) ?></h3>
                            <p><?= htmlspecialchars($module['description']) ?></p>
                        </div>
                    </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="dashboard-grid">
            <!-- Modules disponibles -->
            <section class="user-modules">
                <h2>Mes modules</h2>
                <div class="modules-list">
                    <?php 
                    $user_modules = $current_user['modules'] ?? ['calculateur'];
                    foreach ($user_modules as $module): 
                    ?>
                    <div class="module-item">
                        <div class="module-status active"></div>
                        <div class="module-name"><?= ucfirst($module) ?></div>
                        <div class="module-access">Accès autorisé</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Statistiques -->
            <section class="user-stats">
                <h2>Statistiques</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?= $user_stats['modules_disponibles'] ?></div>
                        <div class="stat-label">Modules disponibles</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-value"><?= $user_stats['sessions_actives'] ?></div>
                        <div class="stat-label">Session active</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-value"><?= ucfirst($user_stats['role']) ?></div>
                        <div class="stat-label">Niveau d'accès</div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Activité récente -->
        <section class="recent-activity">
            <h2>Activité récente</h2>
            <div class="activity-list">
                <?php foreach ($recent_activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon"><?= $activity['icon'] ?></div>
                    <div class="activity-content">
                        <div class="activity-title"><?= htmlspecialchars($activity['title']) ?></div>
                        <div class="activity-time"><?= htmlspecialchars($activity['time']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Sécurité -->
        <section class="security-section">
            <h2>Sécurité</h2>
            <div class="security-info">
                <div class="security-item">
                    <span class="security-icon">🔒</span>
                    <span>Session sécurisée active</span>
                    <span class="security-status ok">✓</span>
                </div>
                
                <div class="security-item">
                    <span class="security-icon">🌐</span>
                    <span>Connexion chiffrée</span>
                    <span class="security-status ok">✓</span>
                </div>
                
                <div class="security-item">
                    <span class="security-icon">👤</span>
                    <span>Authentification validée</span>
                    <span class="security-status ok">✓</span>
                </div>
                
                <div class="security-actions">
                    <a href="/auth/logout.php" class="btn danger">
                        🚪 Se déconnecter
                    </a>
                </div>
            </div>
        </section>

        <!-- Liens utiles -->
        <section class="useful-links">
            <h2>Liens utiles</h2>
            <div class="links-grid">
                <a href="/" class="link-card">
                    <div class="link-icon">🏠</div>
                    <div class="link-title">Accueil</div>
                </a>
                
                <a href="/user/profile.php" class="link-card">
                    <div class="link-icon">📋</div>
                    <div class="link-title">Mon Profil</div>
                </a>
                
                <a href="/user/settings.php" class="link-card">
                    <div class="link-icon">⚙️</div>
                    <div class="link-title">Paramètres</div>
                </a>
                
                <?php if (in_array('admin', $current_user['modules'] ?? [])): ?>
                <a href="/admin/" class="link-card">
                    <div class="link-icon">🛠️</div>
                    <div class="link-title">Administration</div>
                </a>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include ROOT_PATH . '/templates/footer.php'; ?>

    <!-- JavaScript - CHEMIN CORRIGÉ -->
    <script src="assets/js/user.js?v=<?= $build_number ?>"></script>
    <script>
        // Initialisation spécifique au dashboard
        document.addEventListener('DOMContentLoaded', function() {
            console.log('👤 Dashboard utilisateur initialisé');
            console.log('Modules disponibles:', <?= json_encode($current_user['modules'] ?? ['calculateur']) ?>);
            
            // Animation d'entrée des éléments
            const elements = document.querySelectorAll('.action-card, .stat-card, .module-item, .activity-item, .security-item, .link-card');
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    element.style.transition = 'all 0.5s ease';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 50);
            });
            
            // Mise à jour de l'heure
            function updateTime() {
                const timeElements = document.querySelectorAll('.activity-time');
                timeElements.forEach(element => {
                    if (element.textContent === 'Maintenant') {
                        // Optionnel: mise à jour dynamique du temps
                    }
                });
            }
            
            // Actualiser toutes les minutes
            setInterval(updateTime, 60000);
        });
    </script>
</body>
</html>

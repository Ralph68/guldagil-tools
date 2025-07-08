<?php
/**
 * Titre: Page d'accueil du portail Guldagil
 * Chemin: /public/index.php
 * Version: 0.5 beta + build auto
 */

// Protection et initialisation
session_start();
define('ROOT_PATH', dirname(__DIR__));

// Configuration des erreurs selon l'environnement
$is_production = (getenv('APP_ENV') === 'production');
if (!$is_production) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Chargement sécurisé de la configuration
$required_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        http_response_code(500);
        die('<h1>❌ Erreur Configuration</h1><p>Fichier manquant : ' . basename($file) . '</p>');
    }
}

try {
    require_once ROOT_PATH . '/config/config.php';
    require_once ROOT_PATH . '/config/version.php';
} catch (Exception $e) {
    http_response_code(500);
    $error_msg = $is_production ? 'Erreur de configuration' : htmlspecialchars($e->getMessage());
    die('<h1>❌ Erreur</h1><p>' . $error_msg . '</p>');
}

// Vérification BDD
if (!isset($db) || !($db instanceof PDO)) {
    http_response_code(500);
    die('<h1>❌ Erreur Base de Données</h1><p>Connexion non disponible</p>');
}

// AUTHENTIFICATION
$user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$current_user = $user_authenticated ? ($_SESSION['user'] ?? null) : null;

if (!$user_authenticated) {
    header('Location: /auth/login.php');
    exit;
}

// Sécurisation utilisateur par défaut
if (!$current_user) {
    $current_user = [
        'username' => 'Utilisateur',
        'role' => 'user'
    ];
}

// ============================================
// VARIABLES POUR LES TEMPLATES HEADER/FOOTER
// ============================================

// Informations de la page
$page_title = 'Portail Guldagil';
$page_subtitle = 'Solutions professionnelles';
$page_description = 'Portail de gestion - Calcul frais, ADR, contrôle qualité';
$current_module = 'home';

// Variables pour le header
$app_name = 'Guldagil';
$module_css = false; // Pas de CSS spécifique pour l'accueil
$module_js = false;  // Pas de JS spécifique pour l'accueil

// Variables pour le footer
$version_info = [
    'version' => APP_VERSION,
    'build' => BUILD_NUMBER,
    'short_build' => substr(BUILD_NUMBER, 0, 8),
    'date' => BUILD_DATE,
    'year' => date('Y')
];
$show_admin_footer = true;

// Fil d'Ariane
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

$nav_info = 'Tableau de bord principal';

// Configuration des niveaux d'accès
$roles = ['guest' => 0, 'user' => 1, 'manager' => 2, 'admin' => 3, 'dev' => 4];
$user_level = $roles[$current_user['role']] ?? 1;

// Modules disponibles selon le niveau d'accès - COMPLET (6 modules)
$available_modules = [
    'calculateur' => [
        'name' => 'Calculateur de frais',
        'description' => 'Calcul et comparaison des tarifs de transport pour XPO, Heppner et Kuehne+Nagel',
        'icon' => '🧮',
        'color' => 'blue',
        'status' => 'active',
        'status_label' => 'DISPONIBLE',
        'path' => '/calculateur/',
        'features' => ['Comparaison multi-transporteurs', 'Calculs automatisés', 'Export et historique'],
        'min_level' => 1
    ],
    'adr' => [
        'name' => 'Gestion ADR',
        'description' => 'Transport de marchandises dangereuses - Déclarations et suivi réglementaire',
        'icon' => '⚠️',
        'color' => 'orange',
        'status' => 'active',
        'status_label' => 'DISPONIBLE',
        'path' => '/adr/',
        'features' => ['Déclarations ADR', 'Gestion des quotas', 'Suivi réglementaire'],
        'min_level' => 1
    ],
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Contrôle et validation des équipements - Suivi qualité et conformité',
        'icon' => '✅',
        'color' => 'green',
        'status' => 'active',
        'status_label' => 'DISPONIBLE',
        'path' => '/qualite/',
        'features' => ['Tests et validations', 'Rapports de conformité', 'Suivi des équipements'],
        'min_level' => 1
    ],
    'epi' => [
        'name' => 'Équipements EPI',
        'description' => 'Gestion des équipements de protection individuelle - Stock et maintenance',
        'icon' => '🛡️',
        'color' => 'purple',
        'status' => 'development',
        'status_label' => 'EN DÉVELOPPEMENT',
        'path' => '/epi/',
        'features' => ['Inventaire EPI', 'Suivi des dates d\'expiration', 'Gestion des commandes'],
        'min_level' => 1
    ],
    'outillages' => [
        'name' => 'Outillages',
        'description' => 'Gestion des outils et équipements techniques - Maintenance et traçabilité',
        'icon' => '🔧',
        'color' => 'gray',
        'status' => 'development',
        'status_label' => 'EN DÉVELOPPEMENT',
        'path' => '/outillages/',
        'features' => ['Inventaire outillage', 'Planning maintenance', 'Suivi d\'utilisation'],
        'min_level' => 1
    ],
    'admin' => [
        'name' => 'Administration',
        'description' => 'Configuration et gestion globale du portail - Réservé aux administrateurs',
        'icon' => '⚙️',
        'color' => 'red',
        'status' => 'restricted',
        'status_label' => 'ADMINISTRATEURS',
        'path' => '/admin/',
        'features' => ['Configuration système', 'Gestion utilisateurs', 'Maintenance'],
        'min_level' => 3
    ]
];

// Filtrer les modules selon les droits utilisateur
$accessible_modules = array_filter($available_modules, function($module) use ($user_level) {
    return $user_level >= $module['min_level'];
});

// ===============================
// INCLUSION DU HEADER
// ===============================
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    include ROOT_PATH . '/templates/header.php';
} else {
    // Fallback header minimal si template manquant
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>' . htmlspecialchars($page_title) . '</title></head><body>';
}
?>

<!-- ===============================
     CONTENU PRINCIPAL
     =============================== -->
<main class="portal-main">
    <div class="container">
        <!-- Section de bienvenue -->
        <section class="welcome-section">
            <div class="welcome-header">
                <h1 class="welcome-title">
                    Bienvenue, <?= htmlspecialchars($current_user['username']) ?>
                </h1>
                <p class="welcome-subtitle">
                    <?= htmlspecialchars($page_description) ?>
                </p>
            </div>
        </section>

        <!-- Navigation modules -->
        <section class="modules-section">
            <h2 class="section-title">
                <span class="section-icon">🚀</span>
                Vos modules
            </h2>
            
            <div class="modules-grid">
                <?php foreach ($accessible_modules as $module_key => $module): ?>
                <div class="module-card <?= $module['status'] === 'restricted' ? 'module-restricted' : '' ?>">
                    <div class="module-header">
                        <div class="module-icon module-icon-<?= $module['color'] ?>">
                            <?= $module['icon'] ?>
                        </div>
                        <div class="module-status">
                            <?php if ($module['status'] === 'active'): ?>
                                <span class="status-badge status-active">Actif</span>
                            <?php elseif ($module['status'] === 'restricted'): ?>
                                <span class="status-badge status-restricted">Restreint</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="module-content">
                        <h3 class="module-title"><?= htmlspecialchars($module['name']) ?></h3>
                        <p class="module-description"><?= htmlspecialchars($module['description']) ?></p>
                        
                        <ul class="module-features">
                            <?php foreach ($module['features'] as $feature): ?>
                            <li><?= htmlspecialchars($feature) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="module-actions">
                        <a href="<?= htmlspecialchars($module['path']) ?>" 
                           class="module-btn module-btn-<?= $module['color'] ?>">
                            Accéder
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Statistiques système -->
        <?php if ($user_level >= 2): ?>
        <section class="stats-section">
            <h3 class="section-title">
                <span class="section-icon">📊</span>
                Aperçu système
            </h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= count($accessible_modules) ?></div>
                        <div class="stat-label">Modules accessibles</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔧</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= APP_VERSION ?></div>
                        <div class="stat-label">Version portail</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👤</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= ucfirst($current_user['role']) ?></div>
                        <div class="stat-label">Niveau d'accès</div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
</main>

<!-- CSS spécifique à la page d'accueil -->
<style>
    /* Variables pour cohérence avec le thème général */
    .portal-main {
        min-height: calc(100vh - 120px);
        padding: var(--spacing-xl, 2rem) 0;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 var(--spacing-lg, 1.5rem);
    }

    /* Section bienvenue */
    .welcome-section {
        text-align: center;
        margin-bottom: var(--spacing-2xl, 3rem);
    }

    .welcome-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--gray-900, #111827);
        margin-bottom: var(--spacing-md, 1rem);
    }

    .welcome-subtitle {
        font-size: 1.125rem;
        color: var(--gray-600, #4b5563);
        margin: 0;
    }

    /* Sections */
    .section-title {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm, 0.5rem);
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--gray-800, #1f2937);
        margin-bottom: var(--spacing-xl, 2rem);
    }

    .section-icon {
        font-size: 1.25rem;
    }

    /* Grille des modules */
    .modules-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: var(--spacing-xl, 2rem);
        margin-bottom: var(--spacing-2xl, 3rem);
    }

    .module-card {
        background: white;
        border-radius: var(--radius-lg, 0.75rem);
        box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
        overflow: hidden;
        transition: var(--transition-normal, 0.3s ease);
        border: 1px solid var(--gray-200, #e5e7eb);
    }

    .module-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-xl, 0 20px 25px -5px rgba(0, 0, 0, 0.1));
    }

    .module-card.module-restricted {
        opacity: 0.8;
        border-color: var(--gray-300, #d1d5db);
    }

    .module-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--spacing-lg, 1.5rem);
        background: var(--gray-50, #f9fafb);
    }

    .module-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-md, 0.5rem);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .module-icon-blue { background: linear-gradient(135deg, #dbeafe, #bfdbfe); }
    .module-icon-orange { background: linear-gradient(135deg, #fed7aa, #fdba74); }
    .module-icon-green { background: linear-gradient(135deg, #d1fae5, #a7f3d0); }
    .module-icon-purple { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); }
    .module-icon-gray { background: linear-gradient(135deg, #f3f4f6, #e5e7eb); }
    .module-icon-red { background: linear-gradient(135deg, #fecaca, #fca5a5); }

    .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .status-active {
        background: var(--success, #10b981);
        color: white;
    }

    .status-development {
        background: var(--warning, #f59e0b);
        color: white;
    }

    .status-restricted {
        background: var(--error, #ef4444);
        color: white;
    }

    .module-content {
        padding: var(--spacing-lg, 1.5rem);
    }

    .module-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--gray-900, #111827);
        margin-bottom: var(--spacing-sm, 0.5rem);
    }

    .module-description {
        color: var(--gray-600, #4b5563);
        margin-bottom: var(--spacing-md, 1rem);
        line-height: 1.6;
    }

    .module-features {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .module-features li {
        padding: 0.25rem 0;
        color: var(--gray-500, #6b7280);
        font-size: 0.875rem;
    }

    .module-features li:before {
        content: "✓";
        color: var(--success, #10b981);
        margin-right: 0.5rem;
        font-weight: bold;
    }

    .module-actions {
        padding: var(--spacing-lg, 1.5rem);
        border-top: 1px solid var(--gray-200, #e5e7eb);
    }

    .module-btn {
        display: inline-block;
        width: 100%;
        padding: 0.75rem 1rem;
        border-radius: var(--radius-md, 0.5rem);
        text-align: center;
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition-fast, 0.15s ease);
    }

    .module-btn-blue {
        background: var(--primary-blue, #3182ce);
        color: white;
    }

    .module-btn-blue:hover {
        background: var(--primary-blue-dark, #2c5282);
    }

    .module-btn-orange {
        background: #ea580c;
        color: white;
    }

    .module-btn-orange:hover {
        background: #c2410c;
    }

    .module-btn-green {
        background: #059669;
        color: white;
    }

    .module-btn-green:hover {
        background: #047857;
    }

    .module-btn-purple {
        background: #7c3aed;
        color: white;
    }

    .module-btn-purple:hover {
        background: #5b21b6;
    }

    .module-btn-gray {
        background: var(--gray-600, #4b5563);
        color: white;
    }

    .module-btn-gray:hover {
        background: var(--gray-700, #374151);
    }

    .module-btn-red {
        background: #dc2626;
        color: white;
    }

    .module-btn-red:hover {
        background: #b91c1c;
    }

    /* Statistiques */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--spacing-lg, 1.5rem);
    }

    .stat-card {
        background: white;
        padding: var(--spacing-lg, 1.5rem);
        border-radius: var(--radius-lg, 0.75rem);
        box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
        display: flex;
        align-items: center;
        gap: var(--spacing-md, 1rem);
    }

    .stat-icon {
        font-size: 2rem;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--gray-900, #111827);
    }

    .stat-label {
        color: var(--gray-600, #4b5563);
        font-size: 0.875rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .welcome-title {
            font-size: 2rem;
        }

        .modules-grid {
            grid-template-columns: 1fr;
            gap: var(--spacing-lg, 1.5rem);
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 480px) {
        .container {
            padding: 0 var(--spacing-md, 1rem);
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php
// ===============================
// INCLUSION DU FOOTER
// ===============================
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
} else {
    // Fallback footer minimal si template manquant
    echo '<footer style="text-align: center; padding: 2rem; background: #f3f4f6; margin-top: 2rem;">';
    echo '<p>&copy; ' . date('Y') . ' ' . htmlspecialchars($app_name) . ' - Version ' . htmlspecialchars(APP_VERSION) . '</p>';
    echo '</footer></body></html>';
}
?>

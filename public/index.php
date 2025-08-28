<?php
/**
 * Titre: Page d'accueil du portail Guldagil - VERSION AMÉLIORÉE
 * Chemin: /public/index.php
 * Version: 0.6 beta - Design Guldagil + Sécurité renforcée
 */

// Configuration de base (GARDER L'EXISTANT)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Chargement configuration existante
$config_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($config_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// Variables de base pour le template (GARDER L'EXISTANT)
$page_title = 'Portail Guldagil - Solutions de Traitement de l\'Eau';
$page_subtitle = 'Hub Logistique & Qualité Industrielle';
$page_description = 'Portail privé Guldagil - Accès sécurisé aux outils de gestion industrielle';
$current_module = 'home';
$module_css = true; // AJOUTÉ pour charger le CSS home.css

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => true]
];

// Fonctions utilitaires existantes (GARDER)
function shouldShowModule($module_id, $module, $user_role) {
    if (isset($module['admin_only']) && $module['admin_only'] && !in_array($user_role, ['admin', 'dev'])) {
        return false;
    }
    
    if (isset($module['roles']) && !in_array($user_role, $module['roles'])) {
        return false;
    }
    
    return true;
}

function canAccessModule($module_id, $module, $user_role) {
    $status = $module['status'] ?? 'inactive';
    
    if ($status === 'inactive') {
        return false;
    }
    
    if ($status === 'development' && !in_array($user_role, ['admin', 'dev'])) {
        return false;
    }
    
    return shouldShowModule($module_id, $module, $user_role);
}

// INCLURE LE HEADER EXISTANT (authentification via header)
include_once ROOT_PATH . '/templates/header.php';

// Après inclusion du header, les variables $current_user et $user_authenticated sont disponibles

// Redirection obligatoire si non authentifié
if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
    exit;
}

// Modules disponibles (ENRICHIR L'EXISTANT avec thème eau)
$all_modules = [
    'port' => [
        'name' => 'Calculateur Frais de Port',
        'description' => 'Optimisation logistique et calculs transport intelligents',
        'icon' => '📦',
        'url' => '/port/',
        'status' => 'active',
        'color' => '#0ea5e9', // Bleu eau
        'category' => 'Logistique & Transport',
        'priority' => 1,
        'features' => ['Multi-transporteurs', 'ADR intégré', 'Calculs temps réel']
    ],
    'adr' => [
        'name' => 'Marchandises Dangereuses',
        'description' => 'Conformité ADR et gestion sécurisée des produits chimiques',
        'icon' => '⚠️',
        'url' => '/adr/',
        'status' => 'development',
        'color' => '#dc2626',
        'category' => 'Sécurité & Conformité',
        'priority' => 2,
        'features' => ['Base produits', 'Étiquetage auto', 'Conformité réglementaire']
    ],
    'qualite' => [
        'name' => 'Contrôle Qualité',
        'description' => 'Suivi qualité des processus de traitement d\'eau',
        'icon' => '🔬',
        'url' => '/qualite/',
        'status' => 'development',
        'color' => '#059669', // Vert eau
        'category' => 'Qualité & Analyses',
        'priority' => 3,
        'features' => ['Analyses eau', 'Rapports qualité', 'Traçabilité complète']
    ],
    'epi' => [
        'name' => 'Équipements de Protection',
        'description' => 'Gestion centralisée des EPI et équipements sécurité',
        'icon' => '🛡️',
        'url' => '/epi/',
        'status' => 'development',
        'color' => '#f59e0b',
        'category' => 'Sécurité & Conformité',
        'priority' => 4,
        'features' => ['Stock EPI', 'Attributions', 'Alertes expiration']
    ],
    'user' => [
        'name' => 'Mon Espace Personnel',
        'description' => 'Profil utilisateur et préférences personnalisées',
        'icon' => '👤',
        'url' => '/user/',
        'status' => 'active',
        'color' => '#8b5cf6',
        'category' => 'Personnel & Compte',
        'priority' => 5,
        'features' => ['Profil complet', 'Historique', 'Préférences']
    ],
    'admin' => [
        'name' => 'Administration Système',
        'description' => 'Configuration avancée et gestion du portail',
        'icon' => '⚙️',
        'url' => '/admin/',
        'status' => 'active',
        'color' => '#6b7280',
        'category' => 'Système & Configuration',
        'priority' => 6,
        'restricted' => ['admin', 'dev'],
        'features' => ['Dashboard admin', 'Gestion utilisateurs', 'Monitoring système']
    ]
];

// Filtrer les modules selon les droits utilisateur
$user_role = $current_user['role'] ?? 'guest';
$user_modules = [];

foreach ($all_modules as $id => $module) {
    if (canAccessModule($id, $module, $user_role)) {
        $user_modules[$id] = $module;
        $user_modules[$id]['can_access'] = true;
    } else if (shouldShowModule($id, $module, $user_role)) {
        $user_modules[$id] = $module;
        $user_modules[$id]['can_access'] = false;
    }
}

// Statistiques utilisateur
$user_stats = [
    'modules_available' => count(array_filter($user_modules, function($m) { return $m['can_access']; })),
    'modules_total' => count($all_modules),
    'last_connection' => $_SESSION['last_activity'] ?? time(),
    'security_level' => 'Élevé' // Avec IP géolocalisation française
];
?>

<!-- CSS inline spécifique à l'accueil (thème Guldagil eau) -->
<style>
:root {
    --guldagil-primary: #1e40af;
    --guldagil-secondary: #0ea5e9;
    --guldagil-accent: #0891b2;
    --water-light: #22d3ee;
    --water-dark: #155e75;
    --success: #059669;
    --warning: #f59e0b;
    --danger: #dc2626;
    --surface: #ffffff;
    --surface-alt: #f8fafc;
    --text: #1e293b;
    --text-muted: #64748b;
    --border: #e2e8f0;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --radius: 12px;
}

.home-welcome {
    background: linear-gradient(135deg, var(--guldagil-primary), var(--guldagil-secondary));
    color: white;
    padding: 2rem;
    border-radius: var(--radius);
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.home-welcome::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.home-welcome::after {
    content: '💧';
    position: absolute;
    top: 1rem;
    right: 2rem;
    font-size: 2rem;
    opacity: 0.3;
}

.welcome-content h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.welcome-tagline {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 1rem;
}

.welcome-stats {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 600;
}

.company-info {
    background: var(--surface);
    padding: 2rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
    border-left: 4px solid var(--guldagil-accent);
}

.company-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-top: 1rem;
}

.company-feature {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.feature-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--guldagil-secondary), var(--water-light));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.module-card {
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: all 0.3s ease;
    border-left: 4px solid;
}

.module-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.2);
}

.module-header {
    padding: 1.5rem;
    position: relative;
}

.module-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
    display: block;
}

.module-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text);
}

.module-description {
    color: var(--text-muted);
    line-height: 1.5;
    margin-bottom: 1rem;
}

.module-status {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-active {
    background: rgba(5, 150, 105, 0.1);
    color: var(--success);
}

.status-development {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.module-features {
    padding: 0 1.5rem 1.5rem;
}

.features-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.feature-tag {
    background: var(--surface-alt);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.module-action {
    padding: 1rem 1.5rem;
    background: var(--surface-alt);
    border-top: 1px solid var(--border);
}

.btn-module {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--guldagil-primary);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-module:hover {
    background: var(--guldagil-secondary);
    color: white;
    transform: translateY(-1px);
}

.btn-module:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.security-notice {
    background: linear-gradient(135deg, rgba(30, 64, 175, 0.1), rgba(14, 165, 233, 0.1));
    border: 1px solid rgba(30, 64, 175, 0.2);
    padding: 1.5rem;
    border-radius: var(--radius);
    margin-top: 2rem;
}

.security-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--guldagil-primary);
    margin-bottom: 0.5rem;
}

.water-animation {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: -1;
    overflow: hidden;
}

.water-drop {
    position: absolute;
    background: linear-gradient(45deg, var(--water-light), var(--guldagil-accent));
    border-radius: 50% 50% 50% 70%;
    opacity: 0.05;
    animation: fall linear infinite;
}

.drop-1 { left: 10%; width: 8px; height: 10px; animation-duration: 3s; animation-delay: 0s; }
.drop-2 { left: 25%; width: 6px; height: 8px; animation-duration: 4s; animation-delay: 1s; }
.drop-3 { left: 40%; width: 10px; height: 12px; animation-duration: 3.5s; animation-delay: 0.5s; }
.drop-4 { left: 60%; width: 7px; height: 9px; animation-duration: 4.2s; animation-delay: 2s; }
.drop-5 { left: 75%; width: 9px; height: 11px; animation-duration: 3.8s; animation-delay: 1.5s; }
.drop-6 { left: 90%; width: 8px; height: 10px; animation-duration: 3.2s; animation-delay: 0.8s; }

@keyframes fall {
    0% { transform: translateY(-100vh) rotate(0deg); opacity: 0; }
    10% { opacity: 0.05; }
    90% { opacity: 0.05; }
    100% { transform: translateY(100vh) rotate(360deg); opacity: 0; }
}

@media (max-width: 768px) {
    .home-welcome {
        padding: 1.5rem;
    }
    
    .welcome-content h1 {
        font-size: 2rem;
    }
    
    .welcome-stats {
        gap: 1rem;
    }
    
    .company-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .modules-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .water-animation {
        display: none;
    }
    
    .module-card:hover {
        transform: none;
    }
}
</style>

<!-- Animation eau en arrière-plan -->
<div class="water-animation">
    <div class="water-drop drop-1"></div>
    <div class="water-drop drop-2"></div>
    <div class="water-drop drop-3"></div>
    <div class="water-drop drop-4"></div>
    <div class="water-drop drop-5"></div>
    <div class="water-drop drop-6"></div>
</div>

<main class="portal-main">
    <!-- Bannière de bienvenue Guldagil -->
    <section class="home-welcome">
        <div class="welcome-content">
            <h1>Bienvenue sur Guldagil</h1>
            <p class="welcome-tagline">Solutions expertes en traitement de l'eau industrielle</p>
            <div class="welcome-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $user_stats['modules_available'] ?></span>
                    <span>modules accessibles</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $user_stats['security_level'] ?></span>
                    <span>niveau sécurité</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">🇫🇷</span>
                    <span>accès géo-protégé</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Informations entreprise -->
    <section class="company-info">
        <h2>🏭 Notre Expertise</h2>
        <p>Leader dans le traitement de l'eau industrielle, Guldagil propose des solutions complètes pour optimiser vos processus et assurer la conformité réglementaire.</p>
        
        <div class="company-grid">
            <div class="company-feature">
                <div class="feature-icon">💧</div>
                <div>
                    <strong>Traitement Eau</strong>
                    <p>Solutions complètes de purification</p>
                </div>
            </div>
            <div class="company-feature">
                <div class="feature-icon">🔬</div>
                <div>
                    <strong>Analyses Qualité</strong>
                    <p>Contrôles rigoureux et certifiés</p>
                </div>
            </div>
            <div class="company-feature">
                <div class="feature-icon">🏭</div>
                <div>
                    <strong>Solutions Industrielles</strong>
                    <p>Adaptées à votre secteur d'activité</p>
                </div>
            </div>
            <div class="company-feature">
                <div class="feature-icon">🌱</div>
                <div>
                    <strong>Approche Durable</strong>
                    <p>Respect de l'environnement</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Modules disponibles -->
    <section class="modules-section">
        <h2>🚀 Modules Disponibles</h2>
        
        <div class="modules-grid">
            <?php foreach ($user_modules as $id => $module): ?>
            <div class="module-card" style="border-left-color: <?= $module['color'] ?>">
                <div class="module-header">
                    <span class="module-icon"><?= $module['icon'] ?></span>
                    <span class="module-status status-<?= $module['status'] ?>">
                        <?= $module['status'] === 'active' ? 'Actif' : 'Développement' ?>
                    </span>
                    <h3 class="module-title"><?= htmlspecialchars($module['name']) ?></h3>
                    <p class="module-description"><?= htmlspecialchars($module['description']) ?></p>
                </div>
                
                <?php if (isset($module['features'])): ?>
                <div class="module-features">
                    <div class="features-list">
                        <?php foreach ($module['features'] as $feature): ?>
                        <span class="feature-tag"><?= htmlspecialchars($feature) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="module-action">
                    <?php if ($module['can_access']): ?>
                    <a href="<?= $module['url'] ?>" class="btn-module">
                        <span>Accéder</span>
                        <span>→</span>
                    </a>
                    <?php else: ?>
                    <button class="btn-module" disabled>
                        <span>Non disponible</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Notice de sécurité -->
    <section class="security-notice">
        <div class="security-title">
            <span>🔒</span>
            <span>Sécurité Renforcée</span>
        </div>
        <p>Ce portail bénéficie d'une protection avancée : géolocalisation française, détection d'intrusion, et chiffrement des données. Votre navigation est sécurisée et surveillée.</p>
    </section>
</main>

<script>
// JavaScript pour améliorer l'UX (léger)
document.addEventListener('DOMContentLoaded', function() {
    // Animation des cartes au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.6s ease-out forwards';
            }
        });
    }, observerOptions);

    // Appliquer l'observation aux cartes
    document.querySelectorAll('.module-card').forEach(card => {
        observer.observe(card);
    });

    // Console message Guldagil
    console.log(`%c
🌊 PORTAIL GULDAGIL - Solutions Traitement Eau
===============================================
Version: <?= APP_VERSION ?? '0.6' ?>
Sécurité: Renforcée (Géolocalisation FR)
Build: <?= BUILD_NUMBER ?? 'dev' ?>

✅ Authentification réussie
🛡️ Connexion sécurisée
🇫🇷 Accès géographique validé
    `, 'color: #1e40af; font-family: monospace;');
});
</script>

<?php
// Footer existant
include_once ROOT_PATH . '/templates/footer.php';
?>
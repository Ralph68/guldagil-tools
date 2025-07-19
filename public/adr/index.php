<?php
/**
 * Titre: Page d'accueil module ADR - RÉCUPÉRATION COMPLÈTE
 * Chemin: /public/adr/index.php
 * Version: 0.5 beta + build auto
 * Note: Fichier original était tronqué à 72 lignes avec erreur syntaxe
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Démarrage session si pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification authentification portail
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /auth/login.php');
    exit;
}

// Configuration
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Variables pour templates
$page_title = 'Module ADR';
$page_subtitle = 'Gestion des marchandises dangereuses';
$page_description = 'Module ADR - Transport de marchandises dangereuses selon réglementation';
$current_module = 'adr';
$module_css = true;
$current_user = $_SESSION['user'] ?? ['username' => 'Utilisateur', 'role' => 'user'];
$user_authenticated = true;

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '⚠️', 'text' => 'Module ADR', 'url' => '/adr/', 'active' => true]
];

$nav_info = 'Transport de marchandises dangereuses';

// Debug mode
$debug_mode = defined('DEBUG') && DEBUG;

// Simuler données quotas (uniquement XPO et Heppner)
$quotas_data = [
    'xpo' => ['used' => 750, 'limit' => 1000, 'percentage' => 75],
    'heppner' => ['used' => 320, 'limit' => 1000, 'percentage' => 32]
];

// Stats rapides (à connecter aux vraies données)
$quick_stats = [
    'declarations_today' => 12,
    'products_adr' => 180,
    'alerts_active' => 3,
    'last_declaration' => '14:32'
];

// Inclure header
$header_path = ROOT_PATH . '/templates/header.php';
if (file_exists($header_path)) {
    include $header_path;
} else {
    // Header minimal si template non trouvé
    echo '<!DOCTYPE html><html><head>';
    echo '<title>Module ADR</title>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '</head><body>';
}
?>

<!-- CSS spécifique ADR -->
<link rel="stylesheet" href="assets/css/adr.css">

<!-- Container principal -->
<main class="adr-container">
    
    <!-- Debug panel si activé -->
    <?php if ($debug_mode): ?>
    <div class="debug-panel">
        🔧 DEBUG MODE | Session: <?= session_id() ?> | User: <?= htmlspecialchars($current_user['username']) ?> | Role: <?= htmlspecialchars($current_user['role'] ?? 'user') ?>
    </div>
    <?php endif; ?>

    <!-- Hero section avec quotas intégrés -->
    <section class="adr-hero">
        <div class="hero-content">
            <h1>
                <span>⚠️</span>
                <span>Module ADR</span>
            </h1>
            <p>Gestion des marchandises dangereuses selon la réglementation ADR</p>
        </div>
        
        <!-- Quotas en sidebar -->
        <div class="hero-quotas">
            <h3>⚖️ Quotas quotidiens (1000 pts/jour)</h3>
            <?php foreach ($quotas_data as $transporteur => $quota): ?>
            <div class="quota-mini">
                <div class="quota-mini-header">
                    <span class="quota-mini-name"><?= strtoupper($transporteur) ?></span>
                    <span class="quota-mini-value"><?= $quota['used'] ?>/<?= $quota['limit'] ?></span>
                </div>
                <div class="quota-mini-bar">
                    <div class="quota-mini-fill <?= $quota['percentage'] > 80 ? 'high' : ($quota['percentage'] > 50 ? 'medium' : 'low') ?>" 
                         style="width: <?= $quota['percentage'] ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Actions principales -->
    <section class="actions-grid">
        <a href="declare.php" class="action-card primary">
            <span class="action-icon">📝</span>
            <h3 class="action-title">Nouvelle déclaration</h3>
            <p class="action-desc">Créer une déclaration ADR pour transport de marchandises dangereuses</p>
            <span class="btn btn-primary">Déclarer</span>
        </a>
        
        <a href="search.php" class="action-card">
            <span class="action-icon">🔍</span>
            <h3 class="action-title">Recherche produits</h3>
            <p class="action-desc">Consulter la base de données des produits ADR</p>
            <span class="btn btn-outline">Rechercher</span>
        </a>
        
        <a href="manage.php" class="action-card">
            <span class="action-icon">📊</span>
            <h3 class="action-title">Gestion</h3>
            <p class="action-desc">Gérer les expéditions, quotas et paramètres</p>
            <span class="btn btn-outline">Gérer</span>
        </a>
        
        <a href="archives.php" class="action-card">
            <span class="action-icon">📋</span>
            <h3 class="action-title">Archives</h3>
            <p class="action-desc">Consulter et réouvrir les déclarations passées</p>
            <span class="btn btn-outline">Voir archives</span>
        </a>
    </section>

    <!-- Stats rapides -->
    <section class="stats-section">
        <h2 class="stats-title">
            📈 Statistiques du jour
        </h2>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value"><?= $quick_stats['declarations_today'] ?></div>
                <div class="stat-label">Déclarations aujourd'hui</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $quick_stats['products_adr'] ?></div>
                <div class="stat-label">Produits ADR actifs</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $quick_stats['alerts_active'] ?></div>
                <div class="stat-label">Alertes actives</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $quick_stats['last_declaration'] ?></div>
                <div class="stat-label">Dernière déclaration</div>
            </div>
        </div>
    </section>

</main>

<!-- Scripts -->
<script>
// Configuration
const ADR_CONFIG = {
    searchEndpoint: 'ajax/search.php',
    minChars: 1,
    searchDelay: 300
};

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Module ADR chargé');
    
    // Vérifier si ADR.Dashboard existe
    if (typeof window.ADR !== 'undefined' && ADR.Dashboard) {
        ADR.Dashboard.init();
    } else {
        console.log('ℹ️ Dashboard ADR non disponible sur cette page');
    }
});
</script>

<?php
// Inclure footer
$footer_path = ROOT_PATH . '/templates/footer.php';
if (file_exists($footer_path)) {
    include $footer_path;
} else {
    echo '</body></html>';
}
?>

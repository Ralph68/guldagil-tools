<?php
/**
 * Titre: Gestion centralisée des erreurs système
 * Chemin: /public/admin/system/errors.php
 * Version: 0.5 beta + build auto
 */

// Configuration sécurisée
$required_role = ['admin', 'dev'];
require_once dirname(dirname(dirname(__DIR__))) . '/config/config.php';

// Authentification
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: /auth/login.php');
    exit;
}

if (!in_array($_SESSION['user']['role'] ?? 'user', $required_role)) {
    http_response_code(403);
    die('Accès refusé - Rôle insuffisant');
}

// Variables pour template
$page_title = 'Gestion des Erreurs';
$page_subtitle = 'Monitoring et diagnostic système';
$current_module = 'admin';
$user_authenticated = true;

// Initialisation ErrorManager
if (class_exists('ErrorManager')) {
    $errorManager = ErrorManager::getInstance();
    $recent_errors = $errorManager->getRecentErrors(24);
    $error_stats = $errorManager->getErrorStats();
} else {
    // Fallback sur logs classiques
    $recent_errors = [];
    $error_stats = ['total' => 0, 'critical' => 0, 'error' => 0, 'warning' => 0];
}

// Filtres
$filter_level = $_GET['level'] ?? 'all';
$filter_module = $_GET['module'] ?? 'all';
$filter_hours = (int)($_GET['hours'] ?? 24);

// Breadcrumbs
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '⚙️', 'text' => 'Administration', 'url' => '/admin/', 'active' => false],
    ['icon' => '🔧', 'text' => 'Système', 'url' => '/admin/system/', 'active' => false],
    ['icon' => '🚨', 'text' => 'Erreurs', 'url' => '/admin/system/errors.php', 'active' => true]
];

// Chargement header avec template
include ROOT_PATH . '/templates/header.php';
?>

<link rel="stylesheet" href="/admin/assets/css/admin_system.css?v=<?= BUILD_NUMBER ?>">

<main class="admin-content">
    <div class="page-header">
        <div class="page-header-content">
            <h1>🚨 Gestion des Erreurs</h1>
            <p class="page-subtitle">Monitoring et diagnostic système en temps réel</p>
        </div>
        <div class="page-header-actions">
            <a href="/admin/scanner.php" class="btn btn-secondary">
                <span>🔍</span> Scanner
            </a>
            <a href="/admin/logs.php" class="btn btn-secondary">
                <span>📊</span> Logs
            </a>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="stats-grid">
        <div class="stat-card critical">
            <div class="stat-icon">🔥</div>
            <div class="stat-content">
                <div class="stat-number"><?= $error_stats['critical'] ?? 0 ?></div>
                <div class="stat-label">Critiques</div>
            </div>
        </div>
        <div class="stat-card error">
            <div class="stat-icon">❌</div>
            <div class="stat-content">
                <div class="stat-number"><?= $error_stats['error'] ?? 0 ?></div>
                <div class="stat-label">Erreurs</div>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon">⚠️</div>
            <div class="stat-content">
                <div class="stat-number"><?= $error_stats['warning'] ?? 0 ?></div>
                <div class="stat-label">Avertissements</div>
            </div>
        </div>
        <div class="stat-card total">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <div class="stat-number"><?= $error_stats['total'] ?? 0 ?></div>
                <div class="stat-label">Total 24h</div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <label>Niveau</label>
                <select name="level" class="form-control">
                    <option value="all" <?= $filter_level === 'all' ? 'selected' : '' ?>>Tous</option>
                    <option value="critical" <?= $filter_level === 'critical' ? 'selected' : '' ?>>🔥 Critique</option>
                    <option value="error" <?= $filter_level === 'error' ? 'selected' : '' ?>>❌ Erreur</option>
                    <option value="warning" <?= $filter_level === 'warning' ? 'selected' : '' ?>>⚠️ Avertissement</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Période</label>
                <select name="hours" class="form-control">
                    <option value="1" <?= $filter_hours === 1 ? 'selected' : '' ?>>1 heure</option>
                    <option value="6" <?= $filter_hours === 6 ? 'selected' : '' ?>>6 heures</option>
                    <option value="24" <?= $filter_hours === 24 ? 'selected' : '' ?>>24 heures</option>
                    <option value="168" <?= $filter_hours === 168 ? 'selected' : '' ?>>7 jours</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filtrer</button>
        </form>
    </div>

    <!-- Liste des erreurs -->
    <div class="errors-section">
        <div class="section-header">
            <h2>📋 Erreurs récentes</h2>
            <div class="section-actions">
                <button id="autoRefresh" class="btn btn-sm btn-outline-primary">
                    <span>🔄</span> Auto-refresh
                </button>
            </div>
        </div>

        <?php if (empty($recent_errors)): ?>
        <div class="empty-state">
            <div class="empty-icon">✅</div>
            <h3>Aucune erreur trouvée</h3>
            <p>Excellent ! Aucune erreur n'a été détectée dans la période sélectionnée.</p>
        </div>
        <?php else: ?>
        <div class="errors-list" id="errorsList">
            <?php foreach ($recent_errors as $error): ?>
            <div class="error-item" data-level="<?= htmlspecialchars($error['level']) ?>">
                <div class="error-header">
                    <div class="error-level-badge <?= htmlspecialchars($error['level']) ?>">
                        <?= getLevelIcon($error['level']) ?> <?= ucfirst($error['level']) ?>
                    </div>
                    <div class="error-meta">
                        <span class="error-module"><?= htmlspecialchars($error['module']) ?></span>
                        <span class="error-time"><?= date('H:i:s', strtotime($error['timestamp'])) ?></span>
                    </div>
                </div>
                <div class="error-message">
                    <?= htmlspecialchars($error['message']) ?>
                </div>
                <?php if (!empty($error['context'])): ?>
                <div class="error-context">
                    <details>
                        <summary>Détails du contexte</summary>
                        <pre><?= htmlspecialchars(json_encode($error['context'], JSON_PRETTY_PRINT)) ?></pre>
                    </details>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php
function getLevelIcon($level) {
    $icons = [
        'critical' => '🔥',
        'error' => '❌', 
        'warning' => '⚠️',
        'info' => 'ℹ️'
    ];
    return $icons[$level] ?? '⚠️';
}

// Footer si disponible
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
}
?>

<script>
// Auto-refresh toutes les 30 secondes si activé
let autoRefreshEnabled = false;
let refreshInterval;

document.getElementById('autoRefresh').addEventListener('click', function() {
    autoRefreshEnabled = !autoRefreshEnabled;
    
    if (autoRefreshEnabled) {
        this.classList.add('active');
        this.innerHTML = '<span>⏸️</span> Pause';
        refreshInterval = setInterval(() => {
            window.location.reload();
        }, 30000);
    } else {
        this.classList.remove('active');
        this.innerHTML = '<span>🔄</span> Auto-refresh';
        clearInterval(refreshInterval);
    }
});
</script>
<?php
/**
 * Titre: Page Répertoire des Anomalies - Module Contrôle Qualité
 * Chemin: /public/qualite/anomalies.php
 * Version: 0.5 beta + build auto
 */

// Configuration et sécurité
session_start();
define('PORTAL_ACCESS', true);
define('ROOT_PATH', dirname(dirname(__DIR__)));

// Chargement de la configuration
if (file_exists(ROOT_PATH . '/config/config.php')) {
    require_once ROOT_PATH . '/config/config.php';
}
if (file_exists(ROOT_PATH . '/config/version.php')) {
    require_once ROOT_PATH . '/config/version.php';
}

// Variables d'environnement
$current_module = 'qualite';
$page_title = 'Répertoire des Anomalies';
$page_description = 'Consultation et gestion des anomalies par catégorie';
$module_css = true;
$module_js = true;

// Paramètres
$category = $_GET['category'] ?? 'all';
$severity = $_GET['severity'] ?? 'all';
$search = $_GET['search'] ?? '';

// Données d'anomalies simulées
$anomalies_data = [
    [
        'id' => 1,
        'code' => 'ADOU-001',
        'title' => 'Pression d\'eau insuffisante',
        'description' => 'La pression en sortie d\'adoucisseur est inférieure à 2 bars',
        'severity' => 'medium',
        'category' => 'adoucisseur',
        'causes' => ['Filtre pré-adoucisseur encrassé', 'Vanne de service défaillante', 'Résine colmatée'],
        'solutions' => ['Nettoyer ou remplacer le filtre', 'Vérifier et réparer la vanne de service', 'Effectuer un lavage chimique de la résine'],
        'frequency' => 'frequent',
        'equipment' => 'Clack CI, Fleck SXT'
    ],
    [
        'id' => 2,
        'code' => 'ADOU-002',
        'title' => 'Régénération intempestive',
        'description' => 'L\'adoucisseur se régénère trop fréquemment',
        'severity' => 'high',
        'category' => 'adoucisseur',
        'causes' => ['Programmation incorrecte', 'Fuite interne de la vanne', 'Capteur de dureté défaillant'],
        'solutions' => ['Reprogrammer selon les paramètres d\'eau', 'Réparer ou remplacer la vanne', 'Calibrer ou remplacer le capteur'],
        'frequency' => 'occasional',
        'equipment' => 'Tous modèles'
    ],
    [
        'id' => 3,
        'code' => 'POMPE-001',
        'title' => 'Dosage irrégulier',
        'description' => 'Le dosage des produits chimiques est instable',
        'severity' => 'high',
        'category' => 'pompe_doseuse',
        'causes' => ['Membrane usée', 'Calibrage incorrect', 'Bulles d\'air dans le circuit'],
        'solutions' => ['Remplacer la membrane', 'Recalibrer la pompe', 'Purger le circuit'],
        'frequency' => 'frequent',
        'equipment' => 'DOS4-8V, DOS6-12V'
    ]
];

// Filtrage des anomalies
$filtered_anomalies = $anomalies_data;

if ($category !== 'all') {
    $filtered_anomalies = array_filter($filtered_anomalies, function($anomaly) use ($category) {
        return $anomaly['category'] === $category;
    });
}

if ($severity !== 'all') {
    $filtered_anomalies = array_filter($filtered_anomalies, function($anomaly) use ($severity) {
        return $anomaly['severity'] === $severity;
    });
}

if ($search) {
    $filtered_anomalies = array_filter($filtered_anomalies, function($anomaly) use ($search) {
        return stripos($anomaly['title'], $search) !== false ||
               stripos($anomaly['description'], $search) !== false ||
               stripos($anomaly['code'], $search) !== false;
    });
}

// Labels pour affichage
$severity_labels = [
    'low' => 'Faible',
    'medium' => 'Moyenne',
    'high' => 'Élevée',
    'critical' => 'Critique'
];

$category_labels = [
    'adoucisseur' => 'Adoucisseur',
    'pompe_doseuse' => 'Pompe Doseuse',
    'general' => 'Général'
];

$frequency_labels = [
    'rare' => 'Rare',
    'occasional' => 'Occasionnelle',
    'frequent' => 'Fréquente',
    'systematic' => 'Systématique'
];

// Chargement du header
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    require_once ROOT_PATH . '/templates/header.php';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Contrôle Qualité</title>
    
    <style>
    /* CSS minimal pour la page anomalies */
    .qualite-module {
        min-height: 100vh;
        background: linear-gradient(135deg, #f0fdf4 0%, #f9fafb 100%);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        padding: 2rem;
    }

    .module-header {
        background: white;
        border-bottom: 2px solid #10b981;
        padding: 1.5rem 2rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-radius: 12px 12px 0 0;
        margin-bottom: 2rem;
    }

    .breadcrumb {
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }

    .breadcrumb a {
        color: #6b7280;
        text-decoration: none;
    }

    .breadcrumb a:hover {
        color: #10b981;
    }

    .module-header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .module-title {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .module-icon {
        font-size: 2.5rem;
        padding: 0.75rem;
        background: #10b981;
        color: white;
        border-radius: 1rem;
    }

    .module-info h1 {
        margin: 0;
        font-size: 1.875rem;
        font-weight: 700;
        color: #1f2937;
    }

    .module-version {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.875rem;
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .filters-section {
        background: white;
        margin-bottom: 2rem;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 2rem;
    }

    .filters-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #374151;
    }

    .filter-group input,
    .filter-group select {
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 0.875rem;
    }

    .anomalies-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 1.5rem;
    }

    .anomaly-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
        border-left: 4px solid #6b7280;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .anomaly-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .anomaly-card.severity-high { border-left-color: #ef4444; }
    .anomaly-card.severity-medium { border-left-color: #f59e0b; }
    .anomaly-card.severity-low { border-left-color: #10b981; }

    .anomaly-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
    }

    .code-badge {
        background: #374151;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        font-family: monospace;
    }

    .severity-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .severity-high { background: #fef2f2; color: #dc2626; }
    .severity-medium { background: #fef3c7; color: #92400e; }
    .severity-low { background: #ecfdf5; color: #047857; }

    .anomaly-content {
        padding: 1.5rem;
    }

    .anomaly-title {
        margin: 0 0 1rem 0;
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
    }

    .anomaly-description {
        color: #6b7280;
        margin-bottom: 1rem;
        line-height: 1.6;
    }

    .anomaly-details {
        padding: 0 1.5rem 1.5rem;
    }

    .detail-section {
        margin-bottom: 1rem;
    }

    .detail-section h4 {
        margin: 0 0 0.5rem 0;
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
    }

    .causes-list,
    .solutions-list {
        margin: 0;
        padding-left: 1.5rem;
        font-size: 0.875rem;
        color: #6b7280;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .empty-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .qualite-module {
            padding: 1rem;
        }
        
        .anomalies-grid {
            grid-template-columns: 1fr;
        }
        
        .filters-form {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>
<body>

<div class="qualite-module">
    <!-- Header du module -->
    <div class="module-header">
        <div class="breadcrumb">
            <a href="/">🏠 Accueil</a> › 
            <a href="/qualite/">🔬 Contrôle Qualité</a> › 
            <span>⚠️ Répertoire Anomalies</span>
        </div>
        
        <div class="module-header-content">
            <div class="module-title">
                <div class="module-icon">⚠️</div>
                <div class="module-info">
                    <h1>Répertoire des Anomalies</h1>
                    <div class="module-version"><?= count($filtered_anomalies) ?> anomalie(s) trouvée(s)</div>
                </div>
            </div>
            
            <div class="module-actions">
                <a href="/qualite/" class="btn btn-secondary">
                    ← Retour Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <section class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <label for="search">🔍 Recherche</label>
                <input type="text" id="search" name="search" 
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Code, titre, description...">
            </div>
            
            <div class="filter-group">
                <label for="category">📂 Catégorie</label>
                <select id="category" name="category">
                    <option value="all">Toutes les catégories</option>
                    <?php foreach ($category_labels as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $category === $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="severity">🚨 Sévérité</label>
                <select id="severity" name="severity">
                    <option value="all">Toutes les sévérités</option>
                    <?php foreach ($severity_labels as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $severity === $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <button type="submit" class="btn btn-primary" style="background: #10b981; color: white;">
                    🔍 Filtrer
                </button>
            </div>
        </form>
    </section>

    <!-- Liste des anomalies -->
    <section class="anomalies-section">
        <?php if (!empty($filtered_anomalies)): ?>
        <div class="anomalies-grid">
            <?php foreach ($filtered_anomalies as $anomaly): ?>
            <div class="anomaly-card severity-<?= $anomaly['severity'] ?>">
                <div class="anomaly-header">
                    <div class="anomaly-code">
                        <span class="code-badge"><?= htmlspecialchars($anomaly['code']) ?></span>
                    </div>
                    <div class="anomaly-severity">
                        <span class="severity-badge severity-<?= $anomaly['severity'] ?>">
                            <?= $severity_labels[$anomaly['severity']] ?>
                        </span>
                    </div>
                </div>
                
                <div class="anomaly-content">
                    <h3 class="anomaly-title">
                        <?= htmlspecialchars($anomaly['title']) ?>
                    </h3>
                    
                    <p class="anomaly-description">
                        <?= htmlspecialchars($anomaly['description']) ?>
                    </p>
                </div>
                
                <div class="anomaly-details">
                    <div class="detail-section">
                        <h4>🔍 Causes possibles</h4>
                        <ul class="causes-list">
                            <?php foreach ($anomaly['causes'] as $cause): ?>
                            <li><?= htmlspecialchars($cause) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="detail-section">
                        <h4>✅ Solutions recommandées</h4>
                        <ul class="solutions-list">
                            <?php foreach ($anomaly['solutions'] as $solution): ?>
                            <li><?= htmlspecialchars($solution) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- État vide -->
        <div class="empty-state">
            <div class="empty-icon">⚠️</div>
            <h3>Aucune anomalie trouvée</h3>
            <p>Aucune anomalie ne correspond à vos critères de recherche.</p>
        </div>
        <?php endif; ?>
    </section>
</div>

</body>
</html>

<?php
// Chargement du footer
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    require_once ROOT_PATH . '/templates/footer.php';
}
?>
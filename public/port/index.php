<?php
/**
 * Titre: Calculateur de frais de port - Interface moderne
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

session_start();

// Protection et initialisation
define('ROOT_PATH', dirname(__DIR__, 2));

// Chargement configuration
$required_files = [
    ROOT_PATH . '/config/config.php',
    ROOT_PATH . '/config/version.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        http_response_code(500);
        die('<h1>❌ Configuration manquante</h1><p>' . basename($file) . '</p>');
    }
}

try {
    require_once ROOT_PATH . '/config/config.php';
    require_once ROOT_PATH . '/config/version.php';
} catch (Exception $e) {
    http_response_code(500);
    die('<h1>❌ Erreur de chargement</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}

// Authentification
$user_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$current_user = $user_authenticated ? ($_SESSION['user'] ?? null) : null;

if (!$user_authenticated) {
    header('Location: /auth/login.php');
    exit;
}

// Variables template
$page_title = 'Calculateur de frais de port';
$page_subtitle = 'Comparaison des tarifs transport';
$page_description = 'Calculateur de frais de port XPO, Heppner et Kuehne+Nagel';
$current_module = 'calculateur';
$module_css = true;
$module_js = true;

// Fil d'Ariane
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '🧮', 'text' => 'Calculateur', 'url' => '/port/', 'active' => true]
];

$nav_info = 'Calcul des frais de transport';
$show_admin_footer = false;

// Version info
$app_version = defined('APP_VERSION') ? APP_VERSION : '0.5-beta';
$build_number = defined('BUILD_NUMBER') ? BUILD_NUMBER : '00000000';

// Inclure header
if (file_exists(ROOT_PATH . '/templates/header.php')) {
    include ROOT_PATH . '/templates/header.php';
} else {
    // Header fallback intégré
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link rel="stylesheet" href="/assets/css/portal.css?v=<?= htmlspecialchars($build_number) ?>">
    <link rel="stylesheet" href="/assets/css/calculateur.css?v=<?= htmlspecialchars($build_number) ?>">
    
    <!-- CSS critique calculateur -->
    <style>
        /* Variables spécifiques calculateur */
        :root {
            --calc-primary: #3b82f6;
            --calc-secondary: #64748b;
            --calc-success: #10b981;
            --calc-warning: #f59e0b;
            --calc-error: #ef4444;
        }
        
        .calculator-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: var(--spacing-lg, 1.5rem);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-xl, 2rem);
            min-height: calc(100vh - 200px);
        }
        
        .form-panel, .results-panel {
            background: white;
            border-radius: var(--radius-lg, 0.75rem);
            box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
            overflow: hidden;
        }
        
        .panel-header {
            padding: var(--spacing-lg, 1.5rem);
            background: linear-gradient(135deg, var(--calc-primary), #2563eb);
            color: white;
        }
        
        .panel-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .panel-subtitle {
            margin: 0.25rem 0 0;
            opacity: 0.9;
            font-size: 0.875rem;
        }
        
        .form-content {
            padding: var(--spacing-lg, 1.5rem);
        }
        
        .form-group {
            margin-bottom: var(--spacing-lg, 1.5rem);
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: var(--spacing-sm, 0.5rem);
            color: var(--gray-700, #374151);
        }
        
        .form-input {
            width: 100%;
            padding: var(--spacing-md, 1rem);
            border: 2px solid var(--gray-200, #e5e7eb);
            border-radius: var(--radius-md, 0.5rem);
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--calc-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-input.valid {
            border-color: var(--calc-success);
        }
        
        .form-input.invalid {
            border-color: var(--calc-error);
        }
        
        .btn-calculate {
            width: 100%;
            padding: var(--spacing-md, 1rem) var(--spacing-lg, 1.5rem);
            background: var(--calc-primary);
            color: white;
            border: none;
            border-radius: var(--radius-md, 0.5rem);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .btn-calculate:hover:not(:disabled) {
            background: #2563eb;
        }
        
        .btn-calculate:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .results-content {
            padding: var(--spacing-lg, 1.5rem);
            min-height: 300px;
        }
        
        .results-placeholder {
            text-align: center;
            color: var(--gray-500, #6b7280);
            padding: var(--spacing-xl, 2rem);
        }
        
        .carrier-result {
            margin-bottom: var(--spacing-lg, 1.5rem);
            padding: var(--spacing-md, 1rem);
            border: 2px solid var(--gray-200, #e5e7eb);
            border-radius: var(--radius-md, 0.5rem);
            transition: border-color 0.2s ease;
        }
        
        .carrier-result.best {
            border-color: var(--calc-success);
            background: rgba(16, 185, 129, 0.05);
        }
        
        .carrier-name {
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: var(--spacing-sm, 0.5rem);
        }
        
        .carrier-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--calc-primary);
        }
        
        .loading {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm, 0.5rem);
            color: var(--calc-primary);
        }
        
        @media (max-width: 768px) {
            .calculator-container {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg, 1.5rem);
            }
        }
    </style>
</head>
<body>
    <header class="portal-header">
        <div class="header-container" style="max-width: 1400px; margin: 0 auto; padding: var(--spacing-lg, 1.5rem); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="margin: 0; color: white;"><?= htmlspecialchars($page_title) ?></h1>
                <p style="margin: 0; opacity: 0.9;"><?= htmlspecialchars($page_subtitle) ?></p>
            </div>
            <div style="color: white;">
                👤 <?= htmlspecialchars($current_user['username'] ?? 'Utilisateur') ?>
            </div>
        </div>
    </header>
    <main style="background: var(--gray-50, #f9fafb); min-height: calc(100vh - 80px);">
<?php } ?>

<!-- Interface calculateur -->
<div class="calculator-container">
    <!-- Panneau formulaire -->
    <div class="form-panel">
        <div class="panel-header">
            <h2 class="panel-title">📦 Paramètres d'expédition</h2>
            <p class="panel-subtitle">Complétez les informations pour calculer les tarifs</p>
        </div>
        
        <div class="form-content">
            <form id="calculatorForm" novalidate>
                <div class="form-group">
                    <label for="departement" class="form-label">Département de destination *</label>
                    <input type="text" 
                           id="departement" 
                           name="departement" 
                           class="form-input"
                           placeholder="Ex: 67"
                           maxlength="2"
                           required>
                    <small class="form-help">Code département français (01-95)</small>
                </div>
                
                <div class="form-group">
                    <label for="poids" class="form-label">Poids total (kg) *</label>
                    <input type="number" 
                           id="poids" 
                           name="poids" 
                           class="form-input"
                           placeholder="Ex: 25.5"
                           min="0.1"
                           step="0.1"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="type" class="form-label">Type d'envoi</label>
                    <select id="type" name="type" class="form-input">
                        <option value="colis">Colis standard</option>
                        <option value="palette">Palette</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="palettes" class="form-label">Nombre de palettes</label>
                    <input type="number" 
                           id="palettes" 
                           name="palettes" 
                           class="form-input"
                           placeholder="0"
                           min="0"
                           value="0">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Options de service</label>
                    <div style="display: flex; flex-direction: column; gap: var(--spacing-sm, 0.5rem);">
                        <label style="display: flex; align-items: center; gap: var(--spacing-sm, 0.5rem); font-weight: normal;">
                            <input type="checkbox" id="adr" name="adr" value="oui">
                            ⚠️ Matières dangereuses (ADR)
                        </label>
                        <label style="display: flex; align-items: center; gap: var(--spacing-sm, 0.5rem); font-weight: normal;">
                            <input type="checkbox" id="enlevement" name="enlevement">
                            🏭 Enlèvement extérieur
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="option_sup" class="form-label">Service supplémentaire</label>
                    <select id="option_sup" name="option_sup" class="form-input">
                        <option value="standard">Standard</option>
                        <option value="premium13">Premium 13h</option>
                        <option value="rdv">Livraison sur RDV</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-calculate" id="calculateBtn">
                    🧮 Calculer les tarifs
                </button>
            </form>
        </div>
    </div>
    
    <!-- Panneau résultats -->
    <div class="results-panel">
        <div class="panel-header">
            <h2 class="panel-title">💰 Comparatif des tarifs</h2>
            <p class="panel-subtitle">Résultats en temps réel</p>
        </div>
        
        <div class="results-content" id="resultsContent">
            <div class="results-placeholder">
                <div style="font-size: 3rem; margin-bottom: var(--spacing-md, 1rem);">🧮</div>
                <p>Complétez le formulaire pour voir les tarifs</p>
            </div>
        </div>
    </div>
</div>

<?php
// Footer
if (file_exists(ROOT_PATH . '/templates/footer.php')) {
    include ROOT_PATH . '/templates/footer.php';
} else {
?>
    </main>
    
    <script src="/assets/js/calculateur.js?v=<?= htmlspecialchars($build_number) ?>"></script>
    <script>
        // Initialisation du calculateur
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof CalculateurModule !== 'undefined') {
                CalculateurModule.init();
            }
        });
    </script>
</body>
</html>
<?php } ?>

<?php
/**
 * Titre: Module Calculateur - Interface MVC v2.0
 * Chemin: /public/calculateur/index.php
 * Version: 2.0.0 - Build 20250624-001
 */

// Bootstrap
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';
require_once __DIR__ . '/../../src/controllers/CalculateurController.php';

// Session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true
    ]);
}

// Controller MVC
try {
    $controller = new CalculateurController($db);
    
    // Routing simple
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_calculate'])) {
        // AJAX calculation
        header('Content-Type: application/json');
        echo json_encode($controller->calculate($_POST));
        exit;
    }
    
    // Page view
    $viewData = $controller->index($_GET);
    
} catch (Exception $e) {
    error_log("Erreur calculateur: " . $e->getMessage());
    $viewData = [
        'error' => true,
        'message' => 'Service temporairement indisponible',
        'preset_data' => [],
        'options_service' => [],
        'dept_restrictions' => []
    ];
}

// Extract view data
extract($viewData);
$page_title = 'Calculateur de frais de port';
$version_info = getVersionInfo();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
   <link rel="stylesheet" href="../assets/css/modules/calculateur/calculateur-complete.css">
    
    <!-- Meta -->
    <meta name="description" content="Calculateur frais de port - Comparaison XPO, Heppner, Kuehne+Nagel">
    <meta name="theme-color" content="#1e40af">
    <link rel="icon" href="../assets/img/favicon.png">
</head>

<body class="calculateur-app">
    
    <!-- Header -->
    <header class="app-header">
        <div class="container">
            <div class="header-content">
                <div class="brand">
                    <img src="../assets/img/logo_guldagil.png" alt="Guldagil" class="brand-logo">
                    <div class="brand-info">
                        <h1 class="brand-title">Calculateur Frais de Port</h1>
                        <p class="brand-subtitle">Comparaison transporteurs</p>
                    </div>
                </div>
                <div class="version-info">
                    <span>v<?= $version_info['version'] ?></span>
                    <small>Build <?= $version_info['build'] ?></small>
                </div>
            </div>
        </div>
    </header>

    <!-- Alert système -->
    <?php if (isset($error) && $error): ?>
    <div class="system-alert error">
        <div class="container">
            <span class="alert-icon">⚠️</span>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main content -->
    <main class="app-main">
        <div class="container">
            <div class="calc-layout">
                
                <!-- Formulaire -->
                <section class="form-panel">
                    <div class="panel-header">
                        <h2><span class="icon">📦</span> Paramètres d'expédition</h2>
                        <p>Configurez votre envoi pour comparer les tarifs</p>
                    </div>
                    
                    <form id="calc-form" class="calc-form" method="post">
                        
                        <!-- Step 1: Destination -->
                        <div class="form-step" data-step="1">
                            <div class="step-header">
                                <span class="step-number">1</span>
                                <div class="step-info">
                                    <h3>Destination</h3>
                                    <p>Où souhaitez-vous livrer ?</p>
                                </div>
                            </div>
                            
                            <div class="form-field">
                                <label for="departement" class="field-label required">
                                    <span class="label-icon">📍</span>
                                    Département de livraison
                                </label>
                                <select id="departement" name="departement" class="form-control" required>
                                    <option value="">Sélectionnez</option>
                                    <?php for($i = 1; $i <= 95; $i++): 
                                        $dept = sprintf('%02d', $i);
                                        $selected = ($preset_data['departement'] === $dept) ? 'selected' : '';
                                    ?>
                                    <option value="<?= $dept ?>" <?= $selected ?>><?= $dept ?></option>
                                    <?php endfor; ?>
                                    <option value="971" <?= ($preset_data['departement'] === '971') ? 'selected' : '' ?>>971 - Guadeloupe</option>
                                    <option value="972" <?= ($preset_data['departement'] === '972') ? 'selected' : '' ?>>972 - Martinique</option>
                                    <option value="973" <?= ($preset_data['departement'] === '973') ? 'selected' : '' ?>>973 - Guyane</option>
                                    <option value="974" <?= ($preset_data['departement'] === '974') ? 'selected' : '' ?>>974 - Réunion</option>
                                    <option value="976" <?= ($preset_data['departement'] === '976') ? 'selected' : '' ?>>976 - Mayotte</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Step 2: Caractéristiques -->
                        <div class="form-step" data-step="2">
                            <div class="step-header">
                                <span class="step-number">2</span>
                                <div class="step-info">
                                    <h3>Caractéristiques</h3>
                                    <p>Décrivez votre envoi</p>
                                </div>
                            </div>
                            
                            <div class="form-field">
                                <label for="poids" class="field-label required">
                                    <span class="label-icon">⚖️</span>
                                    Poids total (kg)
                                </label>
                                <input type="number" id="poids" name="poids" 
                                       class="form-control" placeholder="Ex: 25" 
                                       min="1" max="32000" step="0.1"
                                       value="<?= htmlspecialchars($preset_data['poids']) ?>" required>
                            </div>
                            
                            <div class="form-field">
                                <label class="field-label required">
                                    <span class="label-icon">📋</span>
                                    Type d'envoi
                                </label>
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="type" value="colis" 
                                               <?= ($preset_data['type'] === 'colis' || empty($preset_data['type'])) ? 'checked' : '' ?>>
                                        <span class="radio-content">
                                            <span class="radio-icon">📦</span>
                                            <span class="radio-text">Colis</span>
                                        </span>
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="type" value="palette" 
                                               <?= ($preset_data['type'] === 'palette') ? 'checked' : '' ?>>
                                        <span class="radio-content">
                                            <span class="radio-icon">🏗️</span>
                                            <span class="radio-text">Palette</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-field" id="field-palettes" style="display: none;">
                                <label for="palettes" class="field-label">
                                    <span class="label-icon">🏗️</span>
                                    Nombre de palettes
                                </label>
                                <input type="number" id="palettes" name="palettes" 
                                       class="form-control" placeholder="Ex: 2" 
                                       min="1" max="20"
                                       value="<?= htmlspecialchars($preset_data['palettes']) ?>">
                            </div>
                        </div>
                        
                        <!-- Step 3: Options -->
                        <div class="form-step" data-step="3">
                            <div class="step-header">
                                <span class="step-number">3</span>
                                <div class="step-info">
                                    <h3>Options</h3>
                                    <p>Personnalisez votre livraison</p>
                                </div>
                            </div>
                            
                            <div class="form-field">
                                <label class="field-label">
                                    <span class="label-icon">⚠️</span>
                                    Matières dangereuses (ADR)
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="adr" value="1" 
                                           <?= ($preset_data['adr']) ? 'checked' : '' ?>>
                                    <span class="checkbox-content">Transport ADR requis</span>
                                </label>
                            </div>
                            
                            <div class="form-field">
                                <label class="field-label">
                                    <span class="label-icon">🚚</span>
                                    Options de service
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="enlevement" value="1" 
                                           <?= ($preset_data['enlevement']) ? 'checked' : '' ?>>
                                    <span class="checkbox-content">Enlèvement à domicile</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="reset-btn">
                                <span class="btn-icon">🔄</span>
                                Réinitialiser
                            </button>
                            <button type="submit" class="btn btn-primary" id="calc-btn">
                                <span class="btn-icon">🧮</span>
                                Calculer
                            </button>
                        </div>
                        
                        <input type="hidden" name="ajax_calculate" value="1">
                    </form>
                </section>
                
                <!-- Résultats -->
                <section class="results-panel">
                    <div class="panel-header">
                        <h2><span class="icon">💰</span> Comparaison des tarifs</h2>
                        <p>Résultats en temps réel</p>
                    </div>
                    
                    <div class="results-content">
                        <div id="results-waiting" class="result-state active">
                            <div class="state-content">
                                <div class="state-icon">⏳</div>
                                <h3>En attente</h3>
                                <p>Remplissez le formulaire pour comparer les tarifs</p>
                            </div>
                        </div>
                        
                        <div id="results-loading" class="result-state">
                            <div class="state-content">
                                <div class="loading-spinner"></div>
                                <h3>Calcul en cours...</h3>
                                <p>Comparaison des transporteurs</p>
                            </div>
                        </div>
                        
                        <div id="results-display" class="result-state">
                            <!-- Injecté par JS -->
                        </div>
                        
                        <div id="results-error" class="result-state">
                            <div class="state-content">
                                <div class="state-icon error">❌</div>
                                <h3>Erreur de calcul</h3>
                                <p>Veuillez réessayer</p>
                                <button class="btn btn-primary" onclick="location.reload()">Recharger</button>
                            </div>
                        </div>
                    </div>
                </section>
                
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="app-footer">
        <div class="container">
            <div class="footer-content">
                <p>&copy; <?= COPYRIGHT_YEAR ?> Guldagil - Transport et Logistique</p>
                <div class="footer-version">
                    <?= renderVersionFooter() ?>
                </div>
            </div>
        </div>
    </footer>

    <!-- Configuration JS -->
    <script>
    window.CalculateurConfig = {
        preset: <?= json_encode($preset_data ?? []) ?>,
        options: <?= json_encode($options_service ?? []) ?>,
        restrictions: <?= json_encode($dept_restrictions ?? []) ?>,
        debug: <?= json_encode(defined('DEBUG') && DEBUG) ?>,
        urls: {
            calculate: window.location.href,
            admin: '../admin/'
        },
        version: '<?= $version_info['version'] ?>',
        build: '<?= $version_info['build'] ?>'
    };
    </script>
    
    <!-- JS modulaire -->
    <script src="../assets/js/modules/calculateur/config.js"></script>
    <script src="../assets/js/modules/calculateur/state-manager.js"></script>
    <script src="../assets/js/modules/calculateur/form-controller.js"></script>
    <script src="../assets/js/modules/calculateur/api-service.js"></script>
    <script src="../assets/js/modules/calculateur/results-controller.js"></script>
    <script src="../assets/js/modules/calculateur/app.js"></script>
    
    <!-- Init -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof CalculateurApp !== 'undefined') {
            new CalculateurApp().init(window.CalculateurConfig);
        }
    });
    </script>
</body>
</html>

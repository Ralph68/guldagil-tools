<?php
/**
 * Titre: Interface User-Friendly avec calcul dynamique
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
    
    // Routing AJAX
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_calculate'])) {
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
    
    <!-- CSS unifié -->
    <link rel="stylesheet" href="../assets/css/modules/calculateur/calculateur-complete.css">
    
    <!-- Meta -->
    <meta name="description" content="Calculateur frais de port - Comparaison XPO, Heppner, Kuehne+Nagel">
    <meta name="theme-color" content="#1e40af">
    <link rel="icon" href="../assets/img/favicon.png">
</head>

<body class="calculateur-app">
    
    <!-- Header moderne -->
    <header class="app-header">
        <div class="container">
            <div class="header-content">
                <div class="brand">
                    <img src="../assets/img/logo_guldagil.png" alt="Guldagil" class="brand-logo">
                    <div class="brand-info">
                        <h1 class="brand-title">Calculateur Frais de Port</h1>
                        <p class="brand-subtitle">Comparaison transporteurs instantanée</p>
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
                
                <!-- Interface de saisie simplifiée -->
                <section class="form-panel">
                    <div class="panel-header">
                        <h2><span class="icon">🧮</span> Calculateur intelligent</h2>
                        <p>Calcul automatique pendant la saisie</p>
                    </div>
                    
                    <form id="calc-form" class="calc-form-compact" method="post">
                        
                        <!-- Interface compacte et intuitive -->
                        <div class="form-grid">
                            
                            <!-- Département -->
                            <div class="form-field">
                                <label for="departement" class="field-label">
                                    📍 Département
                                </label>
                                <select id="departement" name="departement" class="form-control auto-calc" required>
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
                            
                            <!-- Poids -->
                            <div class="form-field">
                                <label for="poids" class="field-label">
                                    ⚖️ Poids (kg)
                                </label>
                                <input type="number" id="poids" name="poids" 
                                       class="form-control auto-calc" 
                                       placeholder="Ex: 150" 
                                       min="0.1" max="32000" step="0.1"
                                       value="<?= htmlspecialchars($preset_data['poids']) ?>" required>
                            </div>
                            
                            <!-- Type -->
                            <div class="form-field">
                                <label class="field-label">📋 Type d'envoi</label>
                                <div class="radio-buttons">
                                    <label class="radio-btn">
                                        <input type="radio" name="type" value="colis" class="auto-calc"
                                               <?= ($preset_data['type'] === 'colis' || empty($preset_data['type'])) ? 'checked' : '' ?>>
                                        <span class="radio-content">
                                            <span class="radio-icon">📦</span>
                                            Colis
                                        </span>
                                    </label>
                                    <label class="radio-btn">
                                        <input type="radio" name="type" value="palette" class="auto-calc"
                                               <?= ($preset_data['type'] === 'palette') ? 'checked' : '' ?>>
                                        <span class="radio-content">
                                            <span class="radio-icon">🏗️</span>
                                            Palette
                                        </span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Palettes (conditionnel) -->
                            <div class="form-field" id="field-palettes" style="display: none;">
                                <label for="palettes" class="field-label">
                                    🏗️ Nombre de palettes
                                </label>
                                <input type="number" id="palettes" name="palettes" 
                                       class="form-control auto-calc" 
                                       placeholder="Ex: 2" 
                                       min="1" max="20"
                                       value="<?= htmlspecialchars($preset_data['palettes']) ?>">
                            </div>
                            
                            <!-- Options en toggle switches -->
                            <div class="form-field">
                                <label class="field-label">⚙️ Options</label>
                                <div class="toggle-switches">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="adr" value="1" class="auto-calc"
                                               <?= ($preset_data['adr']) ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                        <span class="toggle-label">⚠️ Transport ADR</span>
                                    </label>
                                    
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="enlevement" value="1" class="auto-calc"
                                               <?= ($preset_data['enlevement']) ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                        <span class="toggle-label">🚚 Enlèvement</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Options avancées (repliable) -->
                            <div class="form-field">
                                <button type="button" class="toggle-advanced" id="toggle-advanced">
                                    ⚙️ Options avancées <span class="toggle-arrow">▼</span>
                                </button>
                                
                                <div class="advanced-options" id="advanced-options" style="display: none;">
                                    <label class="field-label">🚀 Service de livraison</label>
                                    <select name="service_livraison" class="form-control auto-calc">
                                        <option value="standard">📦 Standard</option>
                                        <option value="rdv">📞 Prise de RDV (+15€)</option>
                                        <option value="datefixe">📅 Date fixe</option>
                                        <option value="premium13">⚡ Premium 13h</option>
                                        <option value="premium18">⚡ Premium 18h</option>
                                    </select>
                                </div>
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
                                <span id="calc-btn-text">Calculer</span>
                            </button>
                        </div>
                        
                        <input type="hidden" name="ajax_calculate" value="1">
                    </form>
                </section>
                
                <!-- Résultats avec détail -->
                <section class="results-panel">
                    <div class="panel-header">
                        <h2><span class="icon">💰</span> Comparaison tarifaire</h2>
                        <p>Résultats instantanés avec détail du calcul</p>
                    </div>
                    
                    <div class="results-content">
                        <!-- État initial -->
                        <div id="results-waiting" class="result-state active">
                            <div class="state-content">
                                <div class="state-icon">⏳</div>
                                <h3>Prêt pour le calcul</h3>
                                <p>Remplissez le formulaire pour voir les tarifs en temps réel</p>
                                <div class="tips">
                                    <div class="tip">💡 <strong>Astuce :</strong> Le calcul se fait automatiquement</div>
                                    <div class="tip">⚡ <strong>Rapidité :</strong> Résultats en moins de 500ms</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- État de chargement avec animation -->
                        <div id="results-loading" class="result-state">
                            <div class="state-content">
                                <div class="loading-animation">
                                    <div class="loading-spinner"></div>
                                    <div class="loading-dots">
                                        <span></span><span></span><span></span>
                                    </div>
                                </div>
                                <h3>Calcul en cours...</h3>
                                <p id="loading-progress">Comparaison des transporteurs</p>
                            </div>
                        </div>
                        
                        <!-- Résultats avec détail Excel -->
                        <div id="results-display" class="result-state">
                            <!-- Injecté par JavaScript -->
                        </div>
                        
                        <!-- État d'erreur -->
                        <div id="results-error" class="result-state">
                            <div class="state-content">
                                <div class="state-icon error">❌</div>
                                <h3>Erreur de calcul</h3>
                                <p id="error-message">Une erreur est survenue lors du calcul</p>
                                <div class="error-actions">
                                    <button class="btn btn-primary" onclick="retryCalculation()">
                                        🔄 Réessayer
                                    </button>
                                    <button class="btn btn-secondary" onclick="contactSupport()">
                                        📞 Support
                                    </button>
                                </div>
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

    <!-- Configuration JavaScript -->
    <script>
    window.CalculateurConfig = {
        preset: <?= json_encode($preset_data ?? []) ?>,
        options: <?= json_encode($options_service ?? []) ?>,
        restrictions: <?= json_encode($dept_restrictions ?? []) ?>,
        debug: <?= json_encode(defined('DEBUG') && DEBUG) ?>,
        
        // URLs
        urls: {
            calculate: window.location.href,
            admin: '../admin/'
        },
        
        // Configuration calcul dynamique
        auto_calc: {
            enabled: true,
            delay: 800,          // 800ms de délai après saisie
            min_fields: 3,       // Minimum de champs requis
            show_progress: true  // Afficher progression
        },
        
        // Métadonnées
        version: '<?= $version_info['version'] ?>',
        build: '<?= $version_info['build'] ?>'
    };
    </script>
    
    <!-- JavaScript modulaire -->
    <script src="../assets/js/modules/calculateur/state-manager.js"></script>
    <script src="../assets/js/modules/calculateur/form-controller.js"></script>
    <script src="../assets/js/modules/calculateur/api-service.js"></script>
    <script src="../assets/js/modules/calculateur/results-controller.js"></script>
    <script src="../assets/js/modules/calculateur/app.js"></script>
    
    <!-- App améliorée avec calcul dynamique -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof CalculateurApp !== 'undefined') {
            const app = new CalculateurApp();
            app.init(window.CalculateurConfig)
                .then(() => {
                    console.log('✅ Calculateur v2.0 opérationnel');
                    
                    // Activation du calcul dynamique
                    enableDynamicCalculation();
                    
                    // Chargement preset si données URL
                    if (hasPresetData()) {
                        setTimeout(() => triggerAutoCalculation(), 500);
                    }
                })
                .catch(err => console.error('❌ Erreur calculateur:', err));
        }
    });
    
    // Calcul dynamique pendant la saisie
    function enableDynamicCalculation() {
        const autoCalcElements = document.querySelectorAll('.auto-calc');
        let calcTimeout;
        
        autoCalcElements.forEach(element => {
            element.addEventListener('input', () => {
                clearTimeout(calcTimeout);
                
                if (isFormValid()) {
                    updateCalcButton('loading');
                    calcTimeout = setTimeout(() => {
                        triggerAutoCalculation();
                    }, window.CalculateurConfig.auto_calc.delay);
                }
            });
            
            element.addEventListener('change', () => {
                clearTimeout(calcTimeout);
                
                // Gestion champ palettes
                if (element.name === 'type') {
                    togglePalettesField();
                }
                
                if (isFormValid()) {
                    updateCalcButton('loading');
                    calcTimeout = setTimeout(() => {
                        triggerAutoCalculation();
                    }, 300); // Plus rapide pour les changements
                }
            });
        });
    }
    
    // Déclencher calcul automatique
    function triggerAutoCalculation() {
        if (window.calculateurApp && window.calculateurApp.formController) {
            const form = document.getElementById('calc-form');
            const event = new Event('submit', { bubbles: true, cancelable: true });
            form.dispatchEvent(event);
        }
    }
    
    // Vérifier validité formulaire
    function isFormValid() {
        const dept = document.getElementById('departement').value;
        const poids = document.getElementById('poids').value;
        const type = document.querySelector('input[name="type"]:checked');
        
        return dept && poids && parseFloat(poids) > 0 && type;
    }
    
    // Mettre à jour bouton calcul
    function updateCalcButton(state) {
        const btn = document.getElementById('calc-btn');
        const text = document.getElementById('calc-btn-text');
        
        switch(state) {
            case 'loading':
                btn.disabled = true;
                text.textContent = 'Calcul...';
                break;
            case 'ready':
                btn.disabled = false;
                text.textContent = 'Calculer';
                break;
            case 'auto':
                btn.disabled = false;
                text.textContent = 'Recalculer';
                break;
        }
    }
    
    // Toggle champ palettes
    function togglePalettesField() {
        const type = document.querySelector('input[name="type"]:checked')?.value;
        const field = document.getElementById('field-palettes');
        
        if (field) {
            field.style.display = type === 'palette' ? 'block' : 'none';
        }
    }
    
    // Toggle options avancées
    document.getElementById('toggle-advanced').addEventListener('click', function() {
        const options = document.getElementById('advanced-options');
        const arrow = this.querySelector('.toggle-arrow');
        
        if (options.style.display === 'none') {
            options.style.display = 'block';
            arrow.textContent = '▲';
        } else {
            options.style.display = 'none';
            arrow.textContent = '▼';
        }
    });
    
    // Fonctions utilitaires
    function hasPresetData() {
        return window.CalculateurConfig.preset && 
               (window.CalculateurConfig.preset.departement || window.CalculateurConfig.preset.poids);
    }
    
    function retryCalculation() {
        triggerAutoCalculation();
    }
    
    function contactSupport() {
        window.open('mailto:support@guldagil.com?subject=Erreur Calculateur', '_blank');
    }
    
    // Initialisation
    togglePalettesField();
    </script>
</body>
</html>

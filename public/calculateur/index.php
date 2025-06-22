<?php
/**
 * Titre: Calculateur de frais de port - Page principale corrigée
 * Chemin: /public/calculateur/index.php
 * Version: 0.5 beta + build
 * 
 * CORRECTION: Résolution du problème d'initialisation des modules
 */

// Configuration et imports
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/Transport.php';

// Démarrage de session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gestion debug
$debug_mode = defined('DEBUG') ? DEBUG : false;

// Configuration options de service
$options_service = [];
$dept_restrictions = [];

try {
    // Charger options de service depuis la base
    $stmt = $db->prepare("SELECT id, nom, description, prix, conditions FROM gul_options_supplementaires WHERE actif = 1 ORDER BY ordre, nom");
    $stmt->execute();
    $options_service = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Charger restrictions départementales si nécessaires
    // (à implémenter selon les besoins)
    $dept_restrictions = [];

} catch (PDOException $e) {
    error_log("Erreur DB dans calculateur/index.php: " . $e->getMessage());
    if ($debug_mode) {
        echo "<!-- Erreur DB: " . htmlspecialchars($e->getMessage()) . " -->";
    }
}

// Variables d'affichage
$page_title = 'Calculateur de frais de port';

// Fonction pour obtenir les infos de version
function getVersionInfo() {
    return [
        'version' => '0.5.0-beta',
        'build' => date('Ymd') . '-' . substr(md5(__FILE__), 0, 6),
        'timestamp' => date('Y-m-d H:i:s'),
        'formatted_date' => date('d/m/Y H:i')
    ];
}

$version_info = getVersionInfo();

// Présets depuis URL ou session (avec sécurisation)
$preset_data = [
    'departement' => filter_input(INPUT_GET, 'dept', FILTER_SANITIZE_STRING) ?? 
                    filter_input(INPUT_GET, 'departement', FILTER_SANITIZE_STRING) ?? 
                    ($_SESSION['calc_dept'] ?? ''),
    'poids' => filter_input(INPUT_GET, 'poids', FILTER_VALIDATE_FLOAT) ?? 
               ($_SESSION['calc_poids'] ?? ''),
    'type' => filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING) ?? 
              ($_SESSION['calc_type'] ?? ''),
    'adr' => filter_input(INPUT_GET, 'adr', FILTER_SANITIZE_STRING) ?? 
             ($_SESSION['calc_adr'] ?? ''),
    'options' => $_GET['options'] ?? ($_SESSION['calc_options'] ?? []),
    'palettes' => filter_input(INPUT_GET, 'palettes', FILTER_VALIDATE_INT) ?? 
                  ($_SESSION['calc_palettes'] ?? ''),
    'enlevement' => isset($_GET['enlevement']) || ($_SESSION['calc_enlevement'] ?? false)
];

// Sauvegarde en session (seulement si valides)
if (!empty($preset_data['departement']) && preg_match('/^\d{2,3}$/', $preset_data['departement'])) {
    $_SESSION['calc_dept'] = $preset_data['departement'];
}
if (!empty($preset_data['poids']) && is_numeric($preset_data['poids'])) {
    $_SESSION['calc_poids'] = $preset_data['poids'];
}
if (!empty($preset_data['type']) && in_array($preset_data['type'], ['colis', 'palette'])) {
    $_SESSION['calc_type'] = $preset_data['type'];
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Gul Transport</title>
    
    <!-- Styles CSS -->
    <link rel="stylesheet" href="/assets/css/modules/calculateur/base.css">
    <link rel="stylesheet" href="/assets/css/modules/calculateur/layout.css">
    <link rel="stylesheet" href="/assets/css/modules/calculateur/components.css">
    <link rel="stylesheet" href="/assets/css/modules/calculateur/results.css">
    <link rel="stylesheet" href="/assets/css/modules/calculateur/progressive-form.css">
    
    <!-- Meta SEO -->
    <meta name="description" content="Calculateur de frais de port - Comparez les tarifs des transporteurs">
    <meta name="robots" content="index,follow">
</head>
<body class="calculateur-page">
    
    <!-- Header -->
    <?php include __DIR__ . '/views/partials/header.php'; ?>

    <!-- Container principal -->
    <div class="calculateur-container">
        
        <!-- Titre principal -->
        <header class="calculateur-header">
            <h1>Calculateur de frais de port</h1>
            <p class="subtitle">Comparez instantanément les tarifs de nos transporteurs partenaires</p>
        </header>

        <!-- Interface principale -->
        <div class="calculateur-interface">
            
            <!-- Panneau formulaire -->
            <div class="form-panel">
                <form id="calculator-form" class="progressive-form" novalidate>
                    
                    <!-- Étape 1: Destination et poids -->
                    <div class="form-step active" data-step="0">
                        <h3>📍 Destination et poids</h3>
                        
                        <div class="form-group">
                            <label for="departement">Département de livraison</label>
                            <input type="text" id="departement" name="departement" 
                                   class="form-input" required
                                   placeholder="Ex: 67, 75, 13..."
                                   pattern="^\d{2,3}$"
                                   value="<?= htmlspecialchars($preset_data['departement']) ?>">
                            <span class="form-hint">Code département (2 ou 3 chiffres)</span>
                            <div id="error-departement" class="field-error"></div>
                        </div>

                        <div class="form-group">
                            <label for="poids">Poids total (kg)</label>
                            <input type="number" id="poids" name="poids" 
                                   class="form-input" required
                                   min="1" max="3000" step="0.1"
                                   placeholder="Ex: 25.5"
                                   value="<?= htmlspecialchars($preset_data['poids']) ?>">
                            <span class="form-hint">Poids entre 1 et 3000 kg</span>
                            <div id="error-poids" class="field-error"></div>
                        </div>
                    </div>

                    <!-- Étape 2: Type d'envoi -->
                    <div class="form-step" data-step="1">
                        <h3>📦 Type d'envoi</h3>
                        
                        <div class="form-group">
                            <fieldset class="radio-group">
                                <legend>Type d'expédition</legend>
                                
                                <label class="radio-label">
                                    <input type="radio" name="type" value="colis" 
                                           <?= $preset_data['type'] === 'colis' ? 'checked' : '' ?>>
                                    <span class="radio-custom"></span>
                                    <span class="radio-text">
                                        <strong>Colis</strong>
                                        <small>Envoi standard en cartons</small>
                                    </span>
                                </label>
                                
                                <label class="radio-label">
                                    <input type="radio" name="type" value="palette" 
                                           <?= $preset_data['type'] === 'palette' ? 'checked' : '' ?>>
                                    <span class="radio-custom"></span>
                                    <span class="radio-text">
                                        <strong>Palette(s)</strong>
                                        <small>Envoi sur palette Europe</small>
                                    </span>
                                </label>
                            </fieldset>
                        </div>

                        <!-- Champ conditionnel pour palettes -->
                        <div id="field-palettes" class="form-group" 
                             style="display: <?= $preset_data['type'] === 'palette' ? 'block' : 'none' ?>">
                            <label for="palettes">Nombre de palettes</label>
                            <select id="palettes" name="palettes" class="form-select">
                                <option value="1" <?= $preset_data['palettes'] == '1' ? 'selected' : '' ?>>1 palette</option>
                                <option value="2" <?= $preset_data['palettes'] == '2' ? 'selected' : '' ?>>2 palettes</option>
                                <option value="3" <?= $preset_data['palettes'] == '3' ? 'selected' : '' ?>>3 palettes</option>
                                <option value="4" <?= $preset_data['palettes'] == '4' ? 'selected' : '' ?>>4 palettes</option>
                            </select>
                        </div>
                    </div>

                    <!-- Étape 3: Options -->
                    <div class="form-step" data-step="2">
                        <h3>⚙️ Options et services</h3>
                        
                        <div class="form-group">
                            <label for="adr">Matières dangereuses (ADR)</label>
                            <select id="adr" name="adr" class="form-select">
                                <option value="non" <?= $preset_data['adr'] !== 'oui' ? 'selected' : '' ?>>Non</option>
                                <option value="oui" <?= $preset_data['adr'] === 'oui' ? 'selected' : '' ?>>Oui (ADR)</option>
                            </select>
                            <span class="form-hint">Produits chimiques, peintures, etc.</span>
                        </div>

                        <div class="form-group">
                            <label for="service_livraison">Service de livraison</label>
                            <select id="service_livraison" name="service_livraison" class="form-select">
                                <option value="standard">Standard</option>
                                <option value="rdv">Prise de rendez-vous</option>
                                <option value="fixe">Date fixe</option>
                                <option value="13h">Premium avant 13h</option>
                                <option value="18h">Premium avant 18h</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="enlevement" name="enlevement" value="1"
                                       <?= $preset_data['enlevement'] ? 'checked' : '' ?>>
                                <span class="checkbox-custom"></span>
                                <span class="checkbox-text">
                                    <strong>Enlèvement à domicile</strong>
                                    <small>Collecte à votre adresse</small>
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Boutons de navigation -->
                    <div class="form-navigation">
                        <button type="button" id="btn-prev" class="btn btn-secondary" style="display: none;">
                            ← Précédent
                        </button>
                        <button type="button" id="btn-next" class="btn btn-primary">
                            Suivant →
                        </button>
                        <button type="submit" id="btn-calculate" class="btn btn-success" style="display: none;">
                            🧮 Calculer
                        </button>
                    </div>

                </form>

                <!-- Indicateur de progression -->
                <div class="progress-indicator">
                    <div class="progress-steps" id="progress-steps">
                        <div class="progress-step active">1</div>
                        <div class="progress-step">2</div>
                        <div class="progress-step">3</div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill" style="width: 33%"></div>
                    </div>
                </div>
            </div>

            <!-- Panneau résultats -->
            <div class="results-panel">
                
                <!-- État vide -->
                <div id="results-empty" class="results-state">
                    <div class="results-empty">
                        <div class="empty-icon">🧮</div>
                        <h3>Prêt à calculer</h3>
                        <p>Remplissez le formulaire pour obtenir les tarifs</p>
                    </div>
                </div>

                <!-- État chargement -->
                <div id="results-loading" class="results-state" style="display: none;">
                    <div class="results-loading">
                        <div class="loading-spinner"></div>
                        <h3>Calcul en cours...</h3>
                        <p>Comparaison des tarifs transporteurs</p>
                    </div>
                </div>

                <!-- État erreur -->
                <div id="results-error" class="results-state" style="display: none;">
                    <div class="results-error">
                        <div class="error-icon">❌</div>
                        <h3>Erreur d'initialisation du calculateur</h3>
                        <p id="error-message">Modules manquants: formController</p>
                        <div class="error-actions">
                            <button type="button" onclick="location.reload()" class="btn btn-secondary">
                                Recharger
                            </button>
                            <button type="button" onclick="history.back()" class="btn btn-secondary">
                                Retour
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Contenu des résultats -->
                <div id="results-content" class="results-content" style="display: none;">
                    <!-- Les résultats seront injectés ici par JavaScript -->
                </div>

            </div>
        </div>

        <!-- Résumé des étapes (affiché sur mobile) -->
        <div class="steps-summary" id="steps-summary">
            <div id="summary-step-0" class="step-summary" style="display: none;">
                <strong>Destination:</strong> <span id="summary-dept"></span> • 
                <strong>Poids:</strong> <span id="summary-poids"></span>kg
            </div>
            <div id="summary-step-1" class="step-summary" style="display: none;">
                <strong>Type:</strong> <span id="summary-type"></span>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/views/partials/footer.php'; ?>

    <!-- Configuration JavaScript -->
    <script>
    // Configuration serveur pour le JS
    window.CalculateurServerConfig = {
        presetData: <?= json_encode($preset_data) ?>,
        optionsService: <?= json_encode($options_service) ?>,
        deptRestrictions: <?= json_encode($dept_restrictions) ?>,
        debugMode: <?= $debug_mode ? 'true' : 'false' ?>,
        version: <?= json_encode($version_info) ?>
    };
    
    // Configuration pour debugging
    window.DEBUG_MODE = <?= $debug_mode ? 'true' : 'false' ?>;
    </script>
    
    <!-- Scripts JavaScript - ORDRE CRITIQUE POUR LA CORRECTION -->
    
    <!-- 1. Module de boot AVANT tout le reste -->
    <script src="/assets/js/modules/calculateur/core/module-boot.js"></script>
    
    <!-- 2. Configuration et core -->
    <script src="/assets/js/modules/calculateur/core/calculateur-config.js"></script>
    <script src="/assets/js/modules/calculateur/core/state-manager.js"></script>
    <script src="/assets/js/modules/calculateur/core/api-service.js"></script>
    
    <!-- 3. Models -->
    <script src="/assets/js/modules/calculateur/models/form-data.js"></script>
    <script src="/assets/js/modules/calculateur/models/validation.js"></script>
    
    <!-- 4. Controllers -->
    <script src="/assets/js/modules/calculateur/controllers/form-controller.js"></script>
    <script src="/assets/js/modules/calculateur/controllers/calculation-controller.js"></script>
    <script src="/assets/js/modules/calculateur/controllers/ui-controller.js"></script>
    
    <!-- 5. Views -->
    <script src="/assets/js/modules/calculateur/views/progressive-form.js"></script>
    <script src="/assets/js/modules/calculateur/views/results-display.js"></script>
    
    <!-- 6. Application principale -->
    <script src="/assets/js/modules/calculateur/main.js"></script>
    
    <!-- Script de fallback et correction -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 DOM chargé, vérification des modules...');
        
        // Attendre un peu que tous les scripts soient chargés
        setTimeout(function() {
            
            // Vérifier si le module boot a réussi
            if (typeof window.moduleBoot !== 'undefined') {
                console.log('✅ Module boot disponible');
                
                // Laisser le module boot gérer l'initialisation
                // Il va créer les instances manquantes et démarrer l'app
                
            } else {
                console.warn('⚠️ Module boot non disponible, activation fallback direct');
                activateFallbackMode();
            }
            
        }, 200); // Délai pour laisser les scripts se charger
    });
    
    // Mode fallback manuel en cas d'échec total
    function activateFallbackMode() {
        console.log('🔄 Activation du mode fallback manuel...');
        
        // Masquer l'erreur et afficher l'état vide
        const errorState = document.getElementById('results-error');
        const emptyState = document.getElementById('results-empty');
        
        if (errorState) errorState.style.display = 'none';
        if (emptyState) emptyState.style.display = 'block';
        
        // Gestion basique du formulaire
        setupBasicFormHandling();
    }
    
    function setupBasicFormHandling() {
        const form = document.getElementById('calculator-form');
        if (!form) return;
        
        // Navigation entre étapes
        let currentStep = 0;
        const steps = form.querySelectorAll('.form-step');
        const btnNext = document.getElementById('btn-next');
        const btnPrev = document.getElementById('btn-prev');
        const btnCalc = document.getElementById('btn-calculate');
        
        function showStep(stepIndex) {
            steps.forEach((step, index) => {
                step.style.display = index === stepIndex ? 'block' : 'none';
                step.classList.toggle('active', index === stepIndex);
            });
            
            // Boutons
            if (btnPrev) btnPrev.style.display = stepIndex > 0 ? 'inline-block' : 'none';
            if (btnNext) btnNext.style.display = stepIndex < steps.length - 1 ? 'inline-block' : 'none';
            if (btnCalc) btnCalc.style.display = stepIndex === steps.length - 1 ? 'inline-block' : 'none';
            
            // Progression
            updateProgress(stepIndex);
        }
        
        function updateProgress(stepIndex) {
            const progressSteps = document.querySelectorAll('.progress-step');
            const progressFill = document.getElementById('progress-fill');
            
            progressSteps.forEach((step, index) => {
                step.classList.toggle('active', index <= stepIndex);
            });
            
            if (progressFill) {
                const percentage = ((stepIndex + 1) / steps.length) * 100;
                progressFill.style.width = percentage + '%';
            }
        }
        
        // Événements navigation
        if (btnNext) {
            btnNext.addEventListener('click', () => {
                if (currentStep < steps.length - 1) {
                    currentStep++;
                    showStep(currentStep);
                }
            });
        }
        
        if (btnPrev) {
            btnPrev.addEventListener('click', () => {
                if (currentStep > 0) {
                    currentStep--;
                    showStep(currentStep);
                }
            });
        }
        
        // Gestion type d'envoi
        const typeRadios = form.querySelectorAll('input[name="type"]');
        const palettesField = document.getElementById('field-palettes');
        
        typeRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                if (palettesField) {
                    palettesField.style.display = radio.value === 'palette' ? 'block' : 'none';
                }
            });
        });
        
        // Soumission du formulaire
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await handleFormSubmit();
        });
        
        if (btnCalc) {
            btnCalc.addEventListener('click', async (e) => {
                e.preventDefault();
                await handleFormSubmit();
            });
        }
        
        // Initialiser l'affichage
        showStep(currentStep);
    }
    
    async function handleFormSubmit() {
        const form = document.getElementById('calculator-form');
        const resultsContent = document.getElementById('results-content');
        const loadingState = document.getElementById('results-loading');
        const emptyState = document.getElementById('results-empty');
        
        // Masquer autres états
        if (emptyState) emptyState.style.display = 'none';
        if (resultsContent) resultsContent.style.display = 'none';
        
        // Afficher loading
        if (loadingState) loadingState.style.display = 'block';
        
        try {
            const formData = new FormData(form);
            
            const response = await fetch('ajax-calculate.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            // Masquer loading
            if (loadingState) loadingState.style.display = 'none';
            
            if (result.success && result.best) {
                displayResults(result);
            } else {
                displayError(result.message || 'Aucun tarif disponible');
            }
            
        } catch (error) {
            console.error('Erreur calcul:', error);
            
            // Masquer loading
            if (loadingState) loadingState.style.display = 'none';
            
            displayError('Erreur de calcul: ' + error.message);
        }
    }
    
    function displayResults(result) {
        const resultsContent = document.getElementById('results-content');
        if (!resultsContent) return;
        
        let html = '<div class="results-success">';
        
        // Meilleur tarif
        if (result.best) {
            html += '<div class="best-result">';
            html += '<h3>🎯 Meilleur tarif</h3>';
            html += `<div class="carrier-best">`;
            html += `<div class="carrier-name">${result.best.transporteur}</div>`;
            html += `<div class="carrier-price">${result.best.prix_total.toFixed(2)}€</div>`;
            html += `</div>`;
            html += '</div>';
        }
        
        // Comparaison
        if (result.carriers && Object.keys(result.carriers).length > 1) {
            html += '<div class="comparison-results">';
            html += '<h4>📊 Comparaison</h4>';
            html += '<div class="comparison-list">';
            
            Object.entries(result.carriers).forEach(([carrier, data]) => {
                if (data && data.prix_total) {
                    const carrierName = carrier.charAt(0).toUpperCase() + carrier.slice(1);
                    html += `<div class="comparison-item">`;
                    html += `<span class="comparison-name">${carrierName}</span>`;
                    html += `<span class="comparison-price">${data.prix_total.toFixed(2)}€</span>`;
                    html += `</div>`;
                }
            });
            
            html += '</div>';
            html += '</div>';
        }
        
        html += '</div>';
        
        resultsContent.innerHTML = html;
        resultsContent.style.display = 'block';
    }
    
    function displayError(message) {
        const resultsContent = document.getElementById('results-content');
        if (!resultsContent) return;
        
        resultsContent.innerHTML = `
            <div class="results-error">
                <div class="error-icon">❌</div>
                <h3>Erreur</h3>
                <p>${message}</p>
                <button onclick="location.reload()" class="btn btn-secondary">Réessayer</button>
            </div>
        `;
        resultsContent.style.display = 'block';
    }
    
    // Gestion des erreurs globales
    window.addEventListener('error', function(event) {
        console.error('Erreur JavaScript:', event.error);
    });
    
    window.addEventListener('unhandledrejection', function(event) {
        console.error('Promesse rejetée:', event.reason);
    });
    
    </script>

    <?php if ($debug_mode): ?>
    <!-- Panel de debug -->
    <div id="debug-panel" class="debug-panel" style="position: fixed; bottom: 10px; right: 10px; background: #000; color: #fff; padding: 10px; border-radius: 5px; font-size: 12px; z-index: 9999;">
        <h4>🐛 Debug Panel</h4>
        <div class="debug-info">
            <strong>Version:</strong> <?= $version_info['version'] ?><br>
            <strong>Build:</strong> <?= $version_info['build'] ?><br>
            <strong>Modules JS:</strong> <span id="debug-modules">Chargement...</span><br>
            <strong>État:</strong> <span id="debug-state">Initialisation...</span>
        </div>
        <script>
        // Mise à jour du debug panel
        setTimeout(function() {
            const modulesSpan = document.getElementById('debug-modules');
            const stateSpan = document.getElementById('debug-state');
            
            if (modulesSpan) {
                const modules = [];
                if (window.CalculateurConfig) modules.push('Config');
                if (window.formController) modules.push('FormCtrl');
                if (window.calcController) modules.push('CalcCtrl');
                if (window.uiController) modules.push('UICtrl');
                if (window.moduleBoot) modules.push('Boot');
                
                modulesSpan.textContent = modules.length > 0 ? modules.join(', ') : 'Aucun';
            }
            
            if (stateSpan) {
                if (window.moduleBoot) {
                    stateSpan.textContent = 'Boot actif';
                } else if (window.CalculateurApp) {
                    stateSpan.textContent = 'App disponible';
                } else {
                    stateSpan.textContent = 'Fallback';
                }
            }
        }, 1000);
        </script>
    </div>
    <?php endif; ?>

</body>
</html>

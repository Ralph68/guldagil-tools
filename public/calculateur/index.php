<?php
/**
 * public/calculateur/index.php
 * Interface calculateur progressive - Étape 1
 * Version: 0.5 beta + build
 */

// Chargement configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

// Informations de version
$version_info = getVersionInfo();
$page_title = 'Calculateur de Frais de Port';

// Session et authentification (développement)
session_start();
$user_authenticated = true;

// Logique de calcul (PRÉSERVÉE)
$results = null;
$validation_errors = [];
$calculation_time = 0;
$debug_info = [];

function validateCalculatorData($data) {
    $errors = [];
    
    if (empty($data['departement'])) {
        $errors['departement'] = 'Département requis';
    } elseif (!preg_match('/^(0[1-9]|[1-8][0-9]|9[0-5]|97[1-6])$/', $data['departement'])) {
        $errors['departement'] = 'Département invalide';
    }
    
    if (empty($data['poids'])) {
        $errors['poids'] = 'Poids requis';
    } elseif (!is_numeric($data['poids']) || $data['poids'] <= 0) {
        $errors['poids'] = 'Poids doit être supérieur à 0';
    } elseif ($data['poids'] > 32000) {
        $errors['poids'] = 'Poids maximum: 32000 kg';
    }
    
    if (empty($data['type'])) {
        $errors['type'] = 'Type d\'envoi requis';
    } elseif (!in_array($data['type'], ['colis', 'palette'])) {
        $errors['type'] = 'Type d\'envoi invalide';
    }
    
    if ($data['type'] === 'palette' && ($data['palettes'] < 0 || $data['palettes'] > 20)) {
        $errors['palettes'] = 'Nombre de palettes invalide (0-20)';
    }
    
    return $errors;
}

// Gestion AJAX pour calcul dynamique
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    
    $params = [
        'departement' => str_pad(trim($_POST['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
        'poids' => floatval($_POST['poids'] ?? 0),
        'type' => strtolower(trim($_POST['type'] ?? 'colis')),
        'adr' => ($_POST['adr'] ?? 'non') === 'oui' ? true : false,
        'option_sup' => trim($_POST['option_sup'] ?? 'standard'),
        'enlevement' => isset($_POST['enlevement']),
        'palettes' => max(0, intval($_POST['palettes'] ?? 0))
    ];

    $validation_errors = validateCalculatorData($params);

    if (empty($validation_errors)) {
        try {
            $transport_file = __DIR__ . '/../../src/modules/calculateur/services/transportcalculateur.php';
            
            if (file_exists($transport_file)) {
                require_once $transport_file;
                $transport = new Transport($db);
                
                $results = $transport->calculateAll($params);
                
                echo json_encode([
                    'success' => true,
                    'results' => $results['results'] ?? [],
                    'debug' => $results['debug'] ?? []
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Service indisponible']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'errors' => $validation_errors]);
    }
    exit;
}

// Traitement formulaire classique (préservé pour fallback)
if ($_POST && !isset($_GET['ajax'])) {
    $start_time = microtime(true);
    
    $params = [
        'departement' => str_pad(trim($_POST['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
        'poids' => floatval($_POST['poids'] ?? 0),
        'type' => strtolower(trim($_POST['type'] ?? 'colis')),
        'adr' => ($_POST['adr'] ?? 'non') === 'oui' ? true : false,
        'option_sup' => trim($_POST['option_sup'] ?? 'standard'),
        'enlevement' => isset($_POST['enlevement']),
        'palettes' => max(0, intval($_POST['palettes'] ?? 0))
    ];

    $validation_errors = validateCalculatorData($params);

    if (empty($validation_errors)) {
        try {
            $transport_file = __DIR__ . '/../../src/modules/calculateur/services/transportcalculateur.php';
            
            if (file_exists($transport_file)) {
                require_once $transport_file;
                $transport = new Transport($db);
                
                $results = $transport->calculateAll($params);
                $debug_info['signature'] = 'array';
                $debug_info['transport_debug'] = $transport->debug ?? [];
            }
            
            $calculation_time = round((microtime(true) - $start_time) * 1000, 2);
            
        } catch (Exception $e) {
            $validation_errors['system'] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>
    
    <!-- CSS existant -->
    <link rel="stylesheet" href="../assets/css/modules/calculateur/modern-interface.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/calculateur-complete.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/ux-improvements.css">
    
    <!-- CSS améliorations -->
    <style>
        /* Couleurs plus vivantes */
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --info: #06b6d4;
            --purple: #8b5cf6;
            --pink: #ec4899;
            --orange: #f97316;
        }
        
        /* Étapes progressives */
        .form-step {
            opacity: 0.4;
            pointer-events: none;
            transition: all 0.3s ease;
        }
        
        .form-step.active {
            opacity: 1;
            pointer-events: auto;
        }
        
        .form-step.completed {
            opacity: 1;
            pointer-events: auto;
            border-left: 4px solid var(--success);
        }
        
        /* Animation des étapes */
        .form-step.active .section-title {
            color: var(--primary);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* Indicateur de progression */
        .progress-bar {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--info));
            width: 0%;
            transition: width 0.5s ease;
        }
        
        /* Bouton reset */
        .btn-reset {
            background: var(--warning);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .btn-reset:hover {
            background: #d97706;
            transform: translateY(-1px);
        }
        
        /* Tooltips */
        .tooltip {
            position: relative;
            cursor: help;
        }
        
        .tooltip:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            white-space: nowrap;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        /* Auto-suggestion poids */
        .weight-suggestion {
            font-size: 0.8rem;
            color: var(--info);
            margin-top: 5px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .weight-suggestion.show {
            opacity: 1;
        }
        
        /* Status loading */
        .calculating {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    
    <meta name="description" content="Calculateur de frais de port pour transporteurs XPO, Heppner et Kuehne+Nagel">
    <meta name="author" content="Guldagil">
</head>
<body class="calculateur-app">
    
    <!-- Header -->
    <header class="app-header">
        <div class="container">
            <div class="header-content">
                <div class="brand">
                    <img src="../assets/img/logo_guldagil.png" alt="Guldagil" class="brand-logo">
                    <div>
                        <h1 class="brand-title">🧮 <?= htmlspecialchars($page_title) ?></h1>
                        <p class="brand-subtitle">Comparateur transporteurs professionnels</p>
                    </div>
                </div>
                <div class="version-info">
                    <div>Version <?= $version_info['version'] ?></div>
                    <div>Build <?= $version_info['build'] ?></div>
                    <button type="button" class="btn-reset" onclick="resetForm()">🔄 Reset</button>
                </div>
            </div>
        </div>
    </header>

    <main class="app-main">
        <div class="container">
            <div class="calc-layout">
                
                <!-- Panneau formulaire progressif -->
                <div class="form-panel">
                    
                    <!-- Barre de progression -->
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressBar"></div>
                    </div>
                    
                    <form id="calc-form" data-dynamic="true">
                        
                        <!-- Étape 1: Destination -->
                        <div class="form-section form-step active" id="step-destination" data-step="1">
                            <h2 class="section-title">📍 Étape 1 - Destination</h2>
                            <p class="section-subtitle">Où souhaitez-vous expédier ?</p>
                            
                            <div class="form-group">
                                <label class="field-label tooltip" for="departement" 
                                       data-tooltip="Département français métropolitain (01-95) ou DOM-TOM (971-976)">
                                    📍 Département de destination
                                </label>
                                <input type="text" id="departement" name="departement" 
                                       class="form-control" placeholder="Ex: 67, 75, 13..." maxlength="3"
                                       value="<?= htmlspecialchars($_POST['departement'] ?? '') ?>" required>
                                <div class="field-help">Saisissez le code département (2 ou 3 chiffres)</div>
                            </div>
                        </div>
                        
                        <!-- Étape 2: Poids -->
                        <div class="form-section form-step" id="step-poids" data-step="2">
                            <h2 class="section-title">⚖️ Étape 2 - Poids</h2>
                            <p class="section-subtitle">Quel est le poids total de votre envoi ?</p>
                            
                            <div class="form-group">
                                <label class="field-label tooltip" for="poids" 
                                       data-tooltip="Poids total brut de l'expédition">
                                    ⚖️ Poids total (kg)
                                </label>
                                <input type="number" id="poids" name="poids" 
                                       class="form-control" step="0.1" min="0.1" max="32000"
                                       placeholder="Ex: 25.5"
                                       value="<?= htmlspecialchars($_POST['poids'] ?? '') ?>" required>
                                <div class="weight-suggestion" id="weightSuggestion"></div>
                                <div class="field-help">Poids brut total incluant l'emballage</div>
                            </div>
                        </div>
                        
                        <!-- Étape 3: Type -->
                        <div class="form-section form-step" id="step-type" data-step="3">
                            <h2 class="section-title">📦 Étape 3 - Type d'envoi</h2>
                            <p class="section-subtitle">Comment est conditionné votre envoi ?</p>
                            
                            <div class="radio-buttons">
                                <label class="radio-btn">
                                    <input type="radio" name="type" value="colis" 
                                           <?= ($_POST['type'] ?? '') === 'colis' ? 'checked' : '' ?>>
                                    <div class="radio-content">
                                        <strong>📦 Colis</strong>
                                        <small>Envoi standard emballé</small>
                                    </div>
                                </label>
                                <label class="radio-btn">
                                    <input type="radio" name="type" value="palette" 
                                           <?= ($_POST['type'] ?? '') === 'palette' ? 'checked' : '' ?>>
                                    <div class="radio-content">
                                        <strong>🚛 Palette</strong>
                                        <small>Expédition palettisée</small>
                                    </div>
                                </label>
                            </div>
                            
                            <!-- Champ palettes (affiché si palette) -->
                            <div class="form-group" id="palettes-field" style="display: none; margin-top: 20px;">
                                <label class="field-label tooltip" for="palettes" 
                                       data-tooltip="Palettes Europe consignées. Peut être différent du nombre total de palettes.">
                                    📊 Palettes EUR consignées
                                </label>
                                <input type="number" id="palettes" name="palettes" 
                                       class="form-control" min="0" max="20" placeholder="0"
                                       value="<?= htmlspecialchars($_POST['palettes'] ?? '0') ?>">
                                <div class="field-help">Nombre de palettes Europe consignées (peut être 0)</div>
                            </div>
                        </div>
                        
                        <!-- Étape 4: ADR -->
                        <div class="form-section form-step adr-section" id="step-adr" data-step="4">
                            <h2 class="section-title">⚠️ Étape 4 - Matières dangereuses</h2>
                            <p class="section-subtitle">Votre envoi contient-il des matières dangereuses (ADR) ?</p>
                            
                            <div class="radio-buttons">
                                <label class="radio-btn">
                                    <input type="radio" name="adr" value="non" 
                                           <?= ($_POST['adr'] ?? '') === 'non' ? 'checked' : '' ?>>
                                    <div class="radio-content">
                                        <strong>✅ Non ADR</strong>
                                        <small>Marchandise normale</small>
                                    </div>
                                </label>
                                <label class="radio-btn">
                                    <input type="radio" name="adr" value="oui" 
                                           <?= ($_POST['adr'] ?? '') === 'oui' ? 'checked' : '' ?>>
                                    <div class="radio-content">
                                        <strong>⚠️ ADR</strong>
                                        <small>Matières dangereuses</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Étape 5: Options -->
                        <div class="form-section form-step" id="step-options" data-step="5">
                            <h2 class="section-title">⚙️ Étape 5 - Options</h2>
                            <p class="section-subtitle">Choisissez vos options de livraison</p>
                            
                            <div class="options-grid">
                                <label class="option-card">
                                    <input type="radio" name="option_sup" value="standard" 
                                           <?= ($_POST['option_sup'] ?? 'standard') === 'standard' ? 'checked' : '' ?>>
                                    <div class="option-title">🚚 Standard</div>
                                    <div class="option-description">Livraison normale</div>
                                    <div class="option-impact">Inclus</div>
                                </label>
                                
                                <label class="option-card">
                                    <input type="radio" name="option_sup" value="rdv">
                                    <div class="option-title">📞 Prise de RDV</div>
                                    <div class="option-description">Appel avant livraison</div>
                                    <div class="option-impact">+ Supplément</div>
                                </label>
                                
                                <label class="option-card">
                                    <input type="radio" name="option_sup" value="premium13">
                                    <div class="option-title">⏰ Premium 13h</div>
                                    <div class="option-description">Livraison avant 13h</div>
                                    <div class="option-impact">+ Supplément</div>
                                </label>
                            </div>
                            
                            <!-- Enlèvement -->
                            <div class="enlevement-section" style="margin-top: 20px;">
                                <label class="checkbox-label tooltip" 
                                       data-tooltip="Collecte de votre marchandise à votre adresse">
                                    <input type="checkbox" name="enlevement" 
                                           <?= isset($_POST['enlevement']) ? 'checked' : '' ?>>
                                    <span>🏠 Enlèvement à domicile</span>
                                </label>
                                <div class="field-help">Collecte de votre marchandise à votre adresse</div>
                            </div>
                        </div>
                        
                    </form>
                </div>
                
                <!-- Panneau résultats -->
                <div class="results-panel">
                    <div class="results-header">
                        <h2>💰 Tarifs</h2>
                        <div class="calculation-status" id="calcStatus">
                            ⏳ En attente de données...
                        </div>
                    </div>
                    
                    <div class="results-content" id="resultsContent">
                        <div class="results-placeholder">
                            <div class="placeholder-icon">🧮</div>
                            <p>Complétez le formulaire pour voir les tarifs</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="app-footer">
        <div class="container">
            <div class="footer-content">
                <div>&copy; <?= date('Y') ?> Guldagil - Version <?= $version_info['version'] ?></div>
                <div>Build <?= $version_info['build'] ?> - <?= $version_info['timestamp'] ?></div>
            </div>
        </div>
    </footer>

    <!-- JavaScript interface progressive -->
    <script>
        // État du formulaire
        let currentStep = 1;
        let isCalculating = false;
        let calculationTimeout = null;
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            initializeForm();
            setupEventListeners();
            updateProgress();
        });
        
        function initializeForm() {
            // Focus sur premier champ
            document.getElementById('departement').focus();
            
            // Vérifier valeurs existantes et avancer si nécessaire
            checkExistingValues();
        }
        
        function setupEventListeners() {
            // Département
            document.getElementById('departement').addEventListener('input', function() {
                if (this.value.length >= 2 && /^(0[1-9]|[1-8][0-9]|9[0-5]|97[1-6])$/.test(this.value)) {
                    completeStep(1);
                    moveToStep(2);
                }
            });
            
            // Poids
            document.getElementById('poids').addEventListener('input', function() {
                const poids = parseFloat(this.value);
                
                // Suggestion automatique palette si > 60kg
                const suggestion = document.getElementById('weightSuggestion');
                if (poids > 60) {
                    suggestion.textContent = '💡 Suggestion: Expédition en palette recommandée (>60kg)';
                    suggestion.classList.add('show');
                    
                    // Auto-sélection palette
                    document.querySelector('input[name="type"][value="palette"]').checked = true;
                    showPalettesField();
                } else {
                    suggestion.classList.remove('show');
                    document.querySelector('input[name="type"][value="colis"]').checked = true;
                    hidePalettesField();
                }
                
                if (poids > 0) {
                    completeStep(2);
                    moveToStep(3);
                }
            });
            
            // Type d'envoi
            document.querySelectorAll('input[name="type"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'palette') {
                        showPalettesField();
                    } else {
                        hidePalettesField();
                    }
                    completeStep(3);
                    moveToStep(4);
                });
            });
            
            // ADR
            document.querySelectorAll('input[name="adr"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    completeStep(4);
                    moveToStep(5);
                    scheduleCalculation();
                });
            });
            
            // Options et enlèvement
            document.querySelectorAll('input[name="option_sup"], input[name="enlevement"]').forEach(input => {
                input.addEventListener('change', function() {
                    completeStep(5);
                    scheduleCalculation();
                });
            });
            
            // Palettes
            document.getElementById('palettes').addEventListener('input', function() {
                scheduleCalculation();
            });
        }
        
        function moveToStep(step) {
            if (step <= currentStep) return;
            
            currentStep = step;
            
            // Activer étape
            document.querySelectorAll('.form-step').forEach((el, index) => {
                if (index + 1 < step) {
                    el.classList.add('completed');
                    el.classList.remove('active');
                } else if (index + 1 === step) {
                    el.classList.add('active');
                    el.classList.remove('completed');
                } else {
                    el.classList.remove('active', 'completed');
                }
            });
            
            // Focus sur premier champ de l'étape
            setTimeout(() => {
                const stepEl = document.getElementById(`step-${getStepName(step)}`);
                const firstInput = stepEl.querySelector('input:not([type="hidden"])');
                if (firstInput) firstInput.focus();
            }, 300);
            
            updateProgress();
        }
        
        function completeStep(step) {
            const stepEl = document.querySelector(`[data-step="${step}"]`);
            stepEl.classList.add('completed');
        }
        
        function getStepName(step) {
            const names = {1: 'destination', 2: 'poids', 3: 'type', 4: 'adr', 5: 'options'};
            return names[step] || 'destination';
        }
        
        function updateProgress() {
            const progress = (currentStep / 5) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
        }
        
        function showPalettesField() {
            document.getElementById('palettes-field').style.display = 'block';
        }
        
        function hidePalettesField() {
            document.getElementById('palettes-field').style.display = 'none';
            document.getElementById('palettes').value = '0';
        }
        
        function scheduleCalculation() {
            if (currentStep < 4) return; // Attendre ADR minimum
            
            clearTimeout(calculationTimeout);
            calculationTimeout = setTimeout(calculateTariffs, 500);
        }
        
        async function calculateTariffs() {
            if (isCalculating) return;
            
            const formData = new FormData(document.getElementById('calc-form'));
            
            // Validation minimale
            if (!formData.get('departement') || !formData.get('poids') || !formData.get('adr')) {
                return;
            }
            
            isCalculating = true;
            document.getElementById('calcStatus').innerHTML = '<span class="spinner"></span> Calcul en cours...';
            document.getElementById('resultsContent').classList.add('calculating');
            
            try {
                const response = await fetch('?ajax=1', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayResults(data.results);
                    document.getElementById('calcStatus').innerHTML = '✅ Tarifs calculés';
                } else {
                    document.getElementById('calcStatus').innerHTML = '❌ Erreur de calcul';
                    console.error('Erreur:', data.error || data.errors);
                }
                
            } catch (error) {
                console.error('Erreur AJAX:', error);
                document.getElementById('calcStatus').innerHTML = '❌ Erreur réseau';
            } finally {
                isCalculating = false;
                document.getElementById('resultsContent').classList.remove('calculating');
            }
        }
        
        function displayResults(results) {
            const validResults = Object.entries(results).filter(([, price]) => price !== null);
            
            if (validResults.length === 0) {
                document.getElementById('resultsContent').innerHTML = 
                    '<div class="error-message">❌ Aucun tarif disponible</div>';
                return;
            }
            
            const bestResult = validResults.reduce((min, curr) => 
                curr[1] < min[1] ? curr : min
            );
            
            let html = `
                <div class="best-rate">
                    <h3>🏆 Meilleur tarif</h3>
                    <div class="best-price">${formatPrice(bestResult[1])}</div>
                    <div class="best-carrier">${bestResult[0].toUpperCase()}</div>
                </div>
                <div class="comparison">
            `;
            
            validResults.forEach(([carrier, price]) => {
                const isBest = carrier === bestResult[0];
                html += `
                    <div class="carrier-row ${isBest ? 'best' : ''}">
                        <span>${carrier.toUpperCase()}</span>
                        <strong>${formatPrice(price)}</strong>
                    </div>
                `;
            });
            
            html += '</div>';
            document.getElementById('resultsContent').innerHTML = html;
        }
        
        function formatPrice(price) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(price);
        }
        
        function resetForm() {
            if (confirm('Voulez-vous vraiment recommencer ?')) {
                document.getElementById('calc-form').reset();
                currentStep = 1;
                
                // Reset visual state
                document.querySelectorAll('.form-step').forEach((el, index) => {
                    if (index === 0) {
                        el.classList.add('active');
                        el.classList.remove('completed');
                    } else {
                        el.classList.remove('active', 'completed');
                    }
                });
                
                hidePalettesField();
                document.getElementById('weightSuggestion').classList.remove('show');
                updateProgress();
                
                // Reset results
                document.getElementById('resultsContent').innerHTML = `
                    <div class="results-placeholder">
                        <div class="placeholder-icon">🧮</div>
                        <p>Complétez le formulaire pour voir les tarifs</p>
                    </div>
                `;
                document.getElementById('calcStatus').innerHTML = '⏳ En attente de données...';
                
                // Focus premier champ
                document.getElementById('departement').focus();
            }
        }
        
        function checkExistingValues() {
            // Vérifier si des valeurs existent déjà et avancer automatiquement
            const dept = document.getElementById('departement').value;
            const poids = document.getElementById('poids').value;
            const type = document.querySelector('input[name="type"]:checked');
            const adr = document.querySelector('input[name="adr"]:checked');
            
            if (dept && /^(0[1-9]|[1-8][0-9]|9[0-5]|97[1-6])$/.test(dept)) {
                completeStep(1);
                currentStep = Math.max(currentStep, 2);
            }
            
            if (poids && parseFloat(poids) > 0) {
                completeStep(2);
                currentStep = Math.max(currentStep, 3);
                
                // Vérifier suggestion palette
                if (parseFloat(poids) > 60) {
                    document.getElementById('weightSuggestion').textContent = 
                        '💡 Suggestion: Expédition en palette recommandée (>60kg)';
                    document.getElementById('weightSuggestion').classList.add('show');
                }
            }
            
            if (type) {
                completeStep(3);
                currentStep = Math.max(currentStep, 4);
                
                if (type.value === 'palette') {
                    showPalettesField();
                }
            }
            
            if (adr) {
                completeStep(4);
                currentStep = Math.max(currentStep, 5);
                
                // Déclencher calcul si tout est prêt
                setTimeout(scheduleCalculation, 100);
            }
            
            // Mettre à jour l'affichage des étapes
            moveToStep(currentStep);
        }
    </script>

</body>
</html>

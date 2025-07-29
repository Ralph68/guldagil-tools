<?php
/**
 * Titre: Calculateur de frais de port - VERSION CORRIGÉE
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

// ⚠️ CONFIGURATION STRICTE pour éviter l'HTML dans l'AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] === 'calculate') {
    // Mode strict AJAX - Pas d'affichage HTML
    ini_set('display_errors', 0);
    error_reporting(0);
} else {
    // Mode normal pour la page
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Configuration et chemins
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

// Variables pour header/footer
$version_info = getVersionInfo();
$page_title = 'Calculateur de Frais de Port';
$page_subtitle = 'Comparaison multi-transporteurs XPO, Heppner, Kuehne+Nagel';
$page_description = 'Calculateur de frais de port professionnel - Comparaison instantanée des tarifs de transport';
$current_module = 'port';
$user_authenticated = true;
$module_css = true;
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '🚛', 'text' => 'Calculateur', 'url' => '/port/', 'active' => true]
];

// Correction session doublée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// 🔐 AUTHENTIFICATION OBLIGATOIRE
// ========================================
if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// ========================================
// 🔧 GESTION AJAX CALCULATE - VERSION ULTRA-PROPRE
// ========================================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'calculate') {
    // Nettoyage buffer pour éviter pollution HTML
    if (ob_get_level()) {
        ob_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        // Récupération des données POST
        $input = file_get_contents('php://input');
        if (empty($input)) {
            throw new Exception('Aucune donnée reçue');
        }
        
        parse_str($input, $post_data);
        
        // Validation et formatage des paramètres
        $params = [
            'departement' => str_pad(trim($post_data['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
            'poids' => floatval($post_data['poids'] ?? 0),
            'type' => strtolower(trim($post_data['type'] ?? 'colis')),
            'adr' => (($post_data['adr'] ?? 'non') === 'oui'),
            'option_sup' => trim($post_data['option_sup'] ?? 'standard'),
            'enlevement' => (($post_data['enlevement'] ?? 'non') === 'oui'),
            'palettes' => max(1, intval($post_data['palettes'] ?? 1)),
            'palette_eur' => intval($post_data['palette_eur'] ?? 0),
            'palette_120x80' => intval($post_data['palette_120x80'] ?? 0),
            'palette_120x100' => intval($post_data['palette_120x100'] ?? 0)
        ];
        
        // Validation des données critiques
        if ($params['poids'] <= 0 || strlen($params['departement']) < 2) {
            throw new Exception('Paramètres invalides: poids et département requis');
        }
        
        // Préparation réponse
        $response = [
            'success' => true,
            'carriers' => [],
            'affretement' => $params['poids'] > 3000,
            'time_ms' => 0,
            'params' => $params,
            'debug' => [
                'calculation_date' => date('Y-m-d H:i:s'),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]
        ];
        
        $start_time = microtime(true);
        
        // TODO: Inclure le moteur de calcul transport
        // require_once ROOT_PATH . '/core/transport/transport.php';
        // $calculator = new TransportCalculator();
        // $results = $calculator->calculateAll($params);
        
        // SIMULATION - À remplacer par le vrai moteur
        $simulated_carriers = ['xpo', 'heppner', 'kuehne_nagel'];
        foreach ($simulated_carriers as $carrier) {
            $base_price = rand(15, 45) + ($params['poids'] * 0.8);
            if ($params['adr']) $base_price *= 1.5;
            if ($params['enlevement']) $base_price += 25;
            
            $prix_ht = round($base_price, 2);
            $prix_ttc = round($prix_ht * 1.2, 2);
            
            $response['carriers'][$carrier] = [
                'prix_ht' => $prix_ht,
                'prix_ttc' => $prix_ttc,
                'delai' => rand(24, 72) . 'h',
                'service' => ucfirst($params['option_sup']),
                'details' => [
                    'poids' => $params['poids'] . 'kg',
                    'destination' => $params['departement'],
                    'adr' => $params['adr'] ? 'Oui' : 'Non',
                    'enlevement' => $params['enlevement'] ? 'Oui' : 'Non'
                ]
            ];
        }
        
        $response['time_ms'] = round((microtime(true) - $start_time) * 1000, 2);
        $response['debug']['carriers_found'] = count($response['carriers']);
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        // Nettoyage buffer en cas d'erreur
        if (ob_get_level()) {
            ob_clean();
        }
        
        $error_response = [
            'success' => false,
            'error' => $e->getMessage(),
            'debug' => [
                'error_file' => basename($e->getFile()),
                'error_line' => $e->getLine(),
                'post_data' => $post_data ?? null,
                'input_received' => strlen($input ?? '') > 0
            ]
        ];
        
        echo json_encode($error_response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ========================================
// 🎨 CHARGEMENT HEADER
// ========================================
include_once ROOT_PATH . '/templates/header.php';
?>

<!-- Styles intégrés optimisés -->
<style>
:root {
    --port-primary: #2563eb;
    --port-bg: #f8fafc;
    --port-panel: #ffffff;
    --port-border: #e2e8f0;
    --port-text: #64748b;
    --port-success: #10b981;
    --port-warning: #f59e0b;
    --port-danger: #ef4444;
}

.calc-container {
    min-height: 100vh;
    background: var(--port-bg);
    padding: 2rem 1rem;
}

.calc-main {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    align-items: start;
}

.calc-header {
    grid-column: 1 / -1;
    text-align: center;
    margin-bottom: 1rem;
    padding: 2rem;
    background: linear-gradient(135deg, var(--port-primary) 0%, #1d4ed8 100%);
    color: white;
    border-radius: 0.75rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.calc-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
}

.calc-form-panel, .calc-results-panel {
    background: var(--port-panel);
    border-radius: 0.75rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    border: 1px solid var(--port-border);
}

.calc-steps {
    display: flex;
    background: #f1f5f9;
    border-bottom: 1px solid var(--port-border);
}

.calc-step-btn {
    flex: 1;
    padding: 1rem;
    background: transparent;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 3px solid transparent;
    color: var(--port-text);
}

.calc-step-btn:hover {
    background: rgba(37, 99, 235, 0.1);
}

.calc-step-btn.active {
    color: var(--port-primary);
    background: var(--port-panel);
    border-bottom-color: var(--port-primary);
}

.calc-step-btn.completed {
    color: var(--port-success);
}

.calc-step-btn.completed::after {
    content: ' ✓';
    font-size: 0.9rem;
}

.calc-form-content, .calc-results-content {
    padding: 2rem;
}

.calc-step-content {
    display: none;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s ease;
}

.calc-step-content.active {
    display: block;
    opacity: 1;
    transform: translateY(0);
}

.calc-form-group {
    margin-bottom: 1.5rem;
}

.calc-form-label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.calc-form-input {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid var(--port-border);
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: all 0.2s;
}

.calc-form-input:focus {
    outline: none;
    border-color: var(--port-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.calc-form-select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='currentColor'%3E%3Cpath fill-rule='evenodd' d='M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' clip-rule='evenodd'/%3E%3C/svg%3E");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5rem;
    padding-right: 2.5rem;
}

.calc-radio-group {
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
}

.calc-radio-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    padding: 0.5rem 1rem;
    border: 2px solid var(--port-border);
    border-radius: 0.5rem;
    transition: all 0.2s;
}

.calc-radio-option:hover {
    border-color: var(--port-primary);
}

.calc-radio-option.selected {
    border-color: var(--port-primary);
    background: rgba(37, 99, 235, 0.05);
}

.calc-btn {
    background: var(--port-primary);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    width: 100%;
    font-size: 1.1rem;
}

.calc-btn:hover:not(:disabled) {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.calc-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

.calc-status {
    text-align: center;
    margin: 1rem 0;
    font-weight: 600;
    color: var(--port-text);
}

.calc-results {
    min-height: 200px;
}

.calc-results-grid {
    display: grid;
    gap: 1rem;
}

.calc-carrier-card {
    padding: 1.5rem;
    border: 2px solid var(--port-border);
    border-radius: 0.75rem;
    background: #fafbfc;
    transition: all 0.2s;
}

.calc-carrier-card:hover {
    border-color: var(--port-primary);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.calc-carrier-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.calc-carrier-name {
    font-weight: 700;
    font-size: 1.1rem;
    color: #1f2937;
    text-transform: uppercase;
}

.calc-carrier-price {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--port-primary);
}

.calc-carrier-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: var(--port-text);
}

.debug-panel {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 350px;
    background: white;
    border: 1px solid var(--port-border);
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    font-size: 12px;
    max-height: 300px;
    overflow: hidden;
}

.debug-header {
    background: var(--port-primary);
    color: white;
    padding: 8px 12px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.debug-content {
    max-height: 250px;
    overflow-y: auto;
    padding: 12px;
    display: none;
}

.debug-panel.expanded .debug-content {
    display: block;
}

.debug-entry {
    margin-bottom: 8px;
    padding: 6px;
    background: #f8fafc;
    border-radius: 4px;
    border-left: 3px solid var(--port-primary);
}

@media (max-width: 768px) {
    .calc-main {
        grid-template-columns: 1fr;
    }
    
    .calc-radio-group {
        flex-direction: column;
    }
    
    .debug-panel {
        width: calc(100% - 20px);
        left: 10px;
        right: 10px;
    }
}
</style>

<!-- Interface principale -->
<div class="calc-container">
    <div class="calc-main">
        <!-- En-tête -->
        <div class="calc-header">
            <h1>🚛 Calculateur de Frais de Port</h1>
            <p>Comparaison instantanée des tarifs XPO, Heppner, Kuehne+Nagel</p>
        </div>
        
        <!-- Formulaire -->
        <div class="calc-form-panel">
            <div class="calc-steps">
                <button class="calc-step-btn active" data-step="1">1. Destination</button>
                <button class="calc-step-btn" data-step="2">2. Colis</button>
                <button class="calc-step-btn" data-step="3">3. Options</button>
            </div>
            
            <div class="calc-form-content">
                <form id="calc-form">
                    <!-- Étape 1: Destination -->
                    <div class="calc-step-content active" data-step="1">
                        <div class="calc-form-group">
                            <label class="calc-form-label" for="departement">Département de destination</label>
                            <input type="text" id="departement" name="departement" class="calc-form-input" placeholder="Ex: 75, 69, 13..." maxlength="2" required>
                        </div>
                    </div>
                    
                    <!-- Étape 2: Colis -->
                    <div class="calc-step-content" data-step="2">
                        <div class="calc-form-group">
                            <label class="calc-form-label" for="poids">Poids total (kg)</label>
                            <input type="number" id="poids" name="poids" class="calc-form-input" placeholder="Ex: 25.5" min="0.1" step="0.1" required>
                        </div>
                        
                        <div class="calc-form-group">
                            <label class="calc-form-label">Type d'envoi</label>
                            <div class="calc-radio-group" id="type-group">
                                <label class="calc-radio-option" data-value="colis">
                                    <input type="radio" name="type" value="colis" checked style="display: none;">
                                    📦 Colis
                                </label>
                                <label class="calc-radio-option" data-value="palette">
                                    <input type="radio" name="type" value="palette" style="display: none;">
                                    🏗️ Palette
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Étape 3: Options -->
                    <div class="calc-step-content" data-step="3">
                        <div class="calc-form-group">
                            <label class="calc-form-label">Marchandises dangereuses (ADR)</label>
                            <div class="calc-radio-group" id="adr-group">
                                <label class="calc-radio-option" data-value="non">
                                    <input type="radio" name="adr" value="non" checked style="display: none;">
                                    ✅ Non
                                </label>
                                <label class="calc-radio-option" data-value="oui">
                                    <input type="radio" name="adr" value="oui" style="display: none;">
                                    ⚠️ Oui
                                </label>
                            </div>
                        </div>
                        
                        <div class="calc-form-group">
                            <label class="calc-form-label">Enlèvement à domicile</label>
                            <div class="calc-radio-group" id="enlevement-group">
                                <label class="calc-radio-option" data-value="non">
                                    <input type="radio" name="enlevement" value="non" checked style="display: none;">
                                    🏢 Dépôt
                                </label>
                                <label class="calc-radio-option" data-value="oui">
                                    <input type="radio" name="enlevement" value="oui" style="display: none;">
                                    🏠 Domicile
                                </label>
                            </div>
                        </div>
                        
                        <div class="calc-form-group">
                            <label class="calc-form-label" for="option_sup">Service</label>
                            <select id="option_sup" name="option_sup" class="calc-form-input calc-form-select">
                                <option value="standard">Standard</option>
                                <option value="express">Express 24h</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        
                        <button type="button" class="calc-btn" id="calc-submit">
                            🚛 Calculer les frais de port
                        </button>
                    </div>
                </form>
                
                <div class="calc-status" id="calc-status">Remplissez le formulaire pour calculer</div>
            </div>
        </div>
        
        <!-- Résultats -->
        <div class="calc-results-panel">
            <div class="calc-results-content">
                <h2>📊 Résultats de comparaison</h2>
                <div class="calc-results" id="calc-results">
                    <p style="text-align: center; color: var(--port-text); margin-top: 2rem;">
                        Les tarifs apparaîtront ici après le calcul
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Panel de débogage -->
<div class="debug-panel" id="debug-panel">
    <div class="debug-header" onclick="toggleDebug()">
        🔧 Debug
        <span id="debug-toggle">▼</span>
    </div>
    <div class="debug-content" id="debug-content">
        <div id="debug-entries"></div>
    </div>
</div>

<script>
// ========================================
// 🎯 CALCULATEUR PORT - JavaScript Principal
// ========================================

const state = {
    currentStep: 1,
    isCalculating: false,
    formData: {}
};

const dom = {
    form: document.getElementById('calc-form'),
    steps: document.querySelectorAll('.calc-step-btn'),
    contents: document.querySelectorAll('.calc-step-content'),
    submitBtn: document.getElementById('calc-submit'),
    status: document.getElementById('calc-status'),
    results: document.getElementById('calc-results'),
    debugPanel: document.getElementById('debug-panel'),
    debugContent: document.getElementById('debug-content'),
    debugEntries: document.getElementById('debug-entries')
};

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initCalculator();
    addDebug('Calculateur initialisé', {
        step: state.currentStep,
        timestamp: new Date().toLocaleTimeString()
    });
});

function initCalculator() {
    setupSteps();
    setupRadioGroups();
    setupFormValidation();
    setupCalculation();
}

function setupSteps() {
    dom.steps.forEach(step => {
        step.addEventListener('click', function() {
            const stepNum = parseInt(this.dataset.step);
            if (stepNum <= getMaxAccessibleStep()) {
                goToStep(stepNum);
            }
        });
    });
}

function setupRadioGroups() {
    document.querySelectorAll('.calc-radio-group').forEach(group => {
        group.addEventListener('click', function(e) {
            const option = e.target.closest('.calc-radio-option');
            if (!option) return;
            
            // Déselectionner toutes les options du groupe
            group.querySelectorAll('.calc-radio-option').forEach(opt => {
                opt.classList.remove('selected');
                opt.querySelector('input').checked = false;
            });
            
            // Sélectionner l'option cliquée
            option.classList.add('selected');
            option.querySelector('input').checked = true;
            
            validateCurrentStep();
        });
    });
    
    // Sélectionner les options par défaut
    document.querySelectorAll('.calc-radio-option input[checked]').forEach(input => {
        input.closest('.calc-radio-option').classList.add('selected');
    });
}

function setupFormValidation() {
    dom.form.addEventListener('input', validateCurrentStep);
    dom.form.addEventListener('change', validateCurrentStep);
}

function setupCalculation() {
    dom.submitBtn.addEventListener('click', performCalculation);
}

function goToStep(stepNum) {
    state.currentStep = stepNum;
    
    // Mettre à jour les boutons d'étapes
    dom.steps.forEach((step, index) => {
        step.classList.remove('active');
        if (index + 1 === stepNum) {
            step.classList.add('active');
        }
    });
    
    // Mettre à jour le contenu
    dom.contents.forEach((content, index) => {
        content.classList.remove('active');
        if (index + 1 === stepNum) {
            content.classList.add('active');
        }
    });
    
    validateCurrentStep();
    addDebug('Navigation étape', { step: stepNum });
}

function getMaxAccessibleStep() {
    // Étape 1: toujours accessible
    if (!isStepValid(1)) return 1;
    
    // Étape 2: accessible si étape 1 valide
    if (!isStepValid(2)) return 2;
    
    // Étape 3: accessible si étapes 1 et 2 valides
    return 3;
}

function isStepValid(stepNum) {
    switch (stepNum) {
        case 1:
            const dept = document.getElementById('departement').value.trim();
            return dept.length >= 2 && /^\d{2,3}$/.test(dept);
        
        case 2:
            const poids = parseFloat(document.getElementById('poids').value);
            return poids > 0;
        
        case 3:
            return true; // Toujours valide car valeurs par défaut
        
        default:
            return false;
    }
}

function validateCurrentStep() {
    const isValid = isStepValid(state.currentStep);
    
    // Marquer l'étape comme complétée
    if (isValid) {
        dom.steps[state.currentStep - 1].classList.add('completed');
        
        // Navigation automatique pour les 2 premières étapes
        if (state.currentStep < 3) {
            setTimeout(() => goToStep(state.currentStep + 1), 500);
        }
    } else {
        dom.steps[state.currentStep - 1].classList.remove('completed');
    }
    
    // Activer le bouton de calcul si toutes les étapes sont valides
    const allValid = isStepValid(1) && isStepValid(2) && isStepValid(3);
    dom.submitBtn.disabled = !allValid || state.isCalculating;
    
    if (allValid && state.currentStep === 3) {
        dom.status.textContent = '✅ Prêt pour le calcul';
    } else {
        dom.status.textContent = 'Remplissez le formulaire pour calculer';
    }
}

async function performCalculation() {
    if (state.isCalculating) return;
    
    state.isCalculating = true;
    dom.form.classList.add('loading');
    dom.status.textContent = '⏳ Calcul en cours...';
    
    try {
        // Collecter les données du formulaire
        const formData = new FormData(dom.form);
        const params = new URLSearchParams(formData);
        
        addDebug('Envoi requête', { params: Object.fromEntries(params) });
        
        const response = await fetch('/port/?ajax=calculate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params.toString()
        });
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP ${response.status}`);
        }
        
        const text = await response.text();
        
        // Vérifier si la réponse est du JSON valide
        if (!text.trim().startsWith('{')) {
            addDebug('Réponse HTML détectée', { textPreview: text.substring(0, 200) });
            throw new Error('Réponse serveur invalide (HTML au lieu de JSON)');
        }
        
        const data = JSON.parse(text);
        addDebug('Réponse JSON reçue', data);
        
        if (data.success) {
            displayResults(data);
            dom.status.textContent = '✅ Calcul terminé';
            showTempMessage('✅ Calcul terminé avec succès', 'success', 2000);
        } else {
            throw new Error(data.error || 'Erreur inconnue');
        }
    } catch (error) {
        console.error('Erreur calcul:', error);
        addDebug('ERREUR', { message: error.message, stack: error.stack });
        dom.status.textContent = '❌ Erreur: ' + error.message;
        showTempMessage('❌ Erreur: ' + error.message, 'warning', 5000);
    } finally {
        state.isCalculating = false;
        dom.form.classList.remove('loading');
    }
}

// Affichage des résultats
function displayResults(data) {
    let html = '<div class="calc-results-wrapper">';
    
    // En-tête avec métadonnées
    html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid var(--port-border);">';
    html += '<h3 style="margin: 0; color: var(--port-primary); font-size: 1.3rem;">';
    
    if (data.affretement) {
        html += '🚛 Affrètement requis';
    } else {
        html += '🚛 Résultats de calcul';
    }
    
    html += '</h3>';
    html += '<small style="color: var(--port-text); font-weight: 500;">Calculé en ' + (data.time_ms || 0) + 'ms</small>';
    html += '</div>';
    
    if (data.carriers && Object.keys(data.carriers).length > 0) {
        html += '<div class="calc-results-grid">';
        
        Object.entries(data.carriers).forEach(([carrier, result]) => {
            const carrierName = {
                'xpo': 'XPO Logistics',
                'heppner': 'Heppner',
                'kuehne_nagel': 'Kuehne+Nagel',
                'info': 'Information'
            }[carrier] || carrier.toUpperCase();
            
            html += '<div class="calc-carrier-card">';
            html += '<div class="calc-carrier-header">';
            html += '<div class="calc-carrier-name">' + carrierName + '</div>';
            html += '<div class="calc-carrier-price">' + result.prix_ttc + '€ TTC</div>';
            html += '</div>';
            
            html += '<div class="calc-carrier-details">';
            html += '<div><strong>HT:</strong> ' + result.prix_ht + '€</div>';
            html += '<div><strong>Délai:</strong> ' + (result.delai || 'N/A') + '</div>';
            html += '<div><strong>Service:</strong> ' + (result.service || 'Standard') + '</div>';
            html += '<div><strong>Poids:</strong> ' + (result.details?.poids || 'N/A') + '</div>';
            
            if (result.message) {
                html += '<div style="grid-column: 1 / -1; margin-top: 0.5rem; padding: 0.5rem; background: rgba(59, 130, 246, 0.1); border-radius: 0.25rem; font-size: 0.85rem;">';
                html += result.message;
                html += '</div>';
            }
            html += '</div>';
            html += '</div>';
        });
        
        html += '</div>';
    } else {
        html += '<div style="text-align: center; padding: 2rem; color: var(--port-text);">';
        html += '❌ Aucun transporteur disponible pour ces critères';
        html += '</div>';
    }
    
    html += '</div>';
    
    dom.results.innerHTML = html;
    
    // Scroll vers les résultats
    dom.results.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Messages temporaires
function showTempMessage(message, type = 'info', duration = 3000) {
    const messageEl = document.createElement('div');
    messageEl.className = `calc-temp-message ${type}`;
    messageEl.textContent = message;
    
    document.body.appendChild(messageEl);
    
    // Afficher
    setTimeout(() => messageEl.classList.add('show'), 100);
    
    // Masquer et supprimer
    setTimeout(() => {
        messageEl.classList.remove('show');
        setTimeout(() => document.body.removeChild(messageEl), 300);
    }, duration);
}

// Système de débogage
function addDebug(title, data = null) {
    const entry = document.createElement('div');
    entry.className = 'debug-entry';
    
    const time = new Date().toLocaleTimeString();
    let content = `<strong>[${time}] ${title}</strong>`;
    
    if (data) {
        content += '<br><pre style="margin: 4px 0 0 0; font-size: 11px; white-space: pre-wrap;">';
        content += JSON.stringify(data, null, 2);
        content += '</pre>';
    }
    
    entry.innerHTML = content;
    dom.debugEntries.appendChild(entry);
    
    // Limiter à 50 entrées
    while (dom.debugEntries.children.length > 50) {
        dom.debugEntries.removeChild(dom.debugEntries.firstChild);
    }
    
    // Auto-scroll
    dom.debugContent.scrollTop = dom.debugContent.scrollHeight;
}

function toggleDebug() {
    dom.debugPanel.classList.toggle('expanded');
    const toggle = document.getElementById('debug-toggle');
    toggle.textContent = dom.debugPanel.classList.contains('expanded') ? '▲' : '▼';
}

// Styles pour les messages temporaires
const tempMessageStyles = `
.calc-temp-message {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 500;
    z-index: 9999;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    max-width: 300px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.calc-temp-message.show {
    transform: translateX(0);
}

.calc-temp-message.success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--port-success);
    border-left: 4px solid var(--port-success);
}

.calc-temp-message.warning {
    background: rgba(245, 158, 11, 0.1);
    color: var(--port-warning);
    border-left: 4px solid var(--port-warning);
}

.calc-temp-message.info {
    background: rgba(37, 99, 235, 0.1);
    color: var(--port-primary);
    border-left: 4px solid var(--port-primary);
}

.calc-form.loading {
    pointer-events: none;
    opacity: 0.7;
}

.calc-form.loading .calc-btn {
    background: #9ca3af;
    cursor: not-allowed;
}
`;

// Injecter les styles
const styleSheet = document.createElement('style');
styleSheet.textContent = tempMessageStyles;
document.head.appendChild(styleSheet);

// Auto-focus sur le premier champ
document.getElementById('departement').focus();
</script>

<?php
// ========================================
// 🎨 CHARGEMENT FOOTER
// ========================================
include_once ROOT_PATH . '/templates/footer.php';
?>
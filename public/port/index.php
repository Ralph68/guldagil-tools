<?php
/**
 * Titre: Calculateur de frais de port - Interface complète avec templates
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

// Variables pour le header
$page_title = 'Calculateur de frais de port';
$page_subtitle = 'Comparateur transporteurs professionnels';
$page_description = 'Calculateur et comparateur de frais de port pour transporteurs XPO et Heppner';
$current_module = 'calculateur';
$nav_info = 'Calcul des frais de transport';

session_start();

// Gestion AJAX pour calculs dynamiques
if (isset($_GET['ajax']) && $_GET['ajax'] === 'calculate') {
    header('Content-Type: application/json');
    
    try {
        // Lecture des données POST
        $input_data = file_get_contents('php://input');
        parse_str($input_data, $post_data);
        
        // Paramètres normalisés
        $params = [
            'departement' => str_pad(trim($post_data['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
            'poids' => floatval($post_data['poids'] ?? 0),
            'type' => strtolower(trim($post_data['type'] ?? 'colis')),
            'adr' => (($post_data['adr'] ?? 'non') === 'oui'),
            'option_sup' => trim($post_data['option_sup'] ?? 'standard'),
            'enlevement' => (($post_data['enlevement'] ?? 'non') === 'oui'),
            'palettes' => max(1, intval($post_data['palettes'] ?? 1)),
        ];
        
        // Validation
        if (empty($params['departement']) || !preg_match('/^(0[1-9]|[1-8][0-9]|9[0-5])$/', $params['departement'])) {
            throw new Exception('Département invalide');
        }
        
        if ($params['poids'] <= 0 || $params['poids'] > 10000) {
            throw new Exception('Poids invalide (1-10000 kg)');
        }
        
        // Charger le calculateur
        $transport_file = __DIR__ . '/../../features/port/transport.php';
        if (!file_exists($transport_file)) {
            throw new Exception('Module de calcul non disponible');
        }
        
        require_once $transport_file;
        $transport = new Transport($db);
        $calculation = $transport->calculateAll($params);
        $results = $calculation['results'] ?? [];
        $best = $calculation['best'] ?? null;
        
        if (empty($results)) {
            throw new Exception('Aucun tarif trouvé pour ces paramètres');
        }
        
        echo json_encode([
            'success' => true,
            'results' => $results,
            'best' => $best,
            'params' => $params,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Inclure le header avec templates
include __DIR__ . '/../../templates/header.php';
?>

<!-- CSS spécifique calculateur -->
<style>
:root {
    --calc-primary: #3b82f6;
    --calc-success: #10b981;
    --calc-warning: #f59e0b;
    --calc-error: #ef4444;
    --calc-gray: #64748b;
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-md: 0.75rem;
    --spacing-lg: 1rem;
    --spacing-xl: 1.5rem;
    --spacing-2xl: 2rem;
    --radius-md: 0.375rem;
    --radius-lg: 0.5rem;
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}

/* Layout principal */
.calc-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--spacing-2xl);
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: var(--spacing-2xl);
    min-height: calc(100vh - 200px);
}

/* Titre de page */
.calc-header {
    grid-column: 1 / -1;
    text-align: center;
    margin-bottom: var(--spacing-2xl);
}

.calc-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--calc-primary);
    margin-bottom: var(--spacing-sm);
}

.calc-subtitle {
    color: var(--calc-gray);
    font-size: 1.125rem;
}

/* Panneau formulaire */
.form-panel {
    background: white;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    border: 1px solid #e5e7eb;
    height: fit-content;
}

.panel-header {
    background: linear-gradient(135deg, var(--calc-primary), #2563eb);
    color: white;
    padding: var(--spacing-xl);
    text-align: center;
}

.panel-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

/* Navigation étapes */
.steps-nav {
    display: flex;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}

.step-btn {
    flex: 1;
    padding: var(--spacing-md);
    background: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
    position: relative;
}

.step-btn.active {
    background: white;
    color: var(--calc-primary);
    border-bottom: 2px solid var(--calc-primary);
}

.step-btn.completed {
    color: var(--calc-success);
}

.step-btn:hover:not(.active) {
    background: #f1f5f9;
}

/* Contenu formulaire */
.form-content {
    padding: var(--spacing-xl);
}

.form-step {
    display: none;
}

.form-step.active {
    display: block;
    animation: slideIn 0.3s ease-in-out;
}

.form-group {
    margin-bottom: var(--spacing-lg);
}

.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: var(--spacing-sm);
    color: #374151;
    font-size: 0.875rem;
}

.form-input {
    width: 100%;
    padding: var(--spacing-md);
    border: 2px solid #e5e7eb;
    border-radius: var(--radius-md);
    font-size: 1rem;
    transition: all 0.2s ease;
    background: white;
}

.form-input:focus {
    outline: none;
    border-color: var(--calc-primary);
    box-shadow: 0 0 0 3px rgb(59 130 246 / 0.1);
}

.form-input.valid {
    border-color: var(--calc-success);
    background: #f0fdf4;
}

.form-input.invalid {
    border-color: var(--calc-error);
    background: #fef2f2;
}

/* Boutons tactiles pour ADR/Enlèvement */
.toggle-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-md);
}

.toggle-btn {
    padding: var(--spacing-md) var(--spacing-lg);
    border: 2px solid #e5e7eb;
    border-radius: var(--radius-md);
    background: white;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
    font-weight: 500;
    min-height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toggle-btn:hover {
    border-color: var(--calc-primary);
    box-shadow: var(--shadow-md);
}

.toggle-btn.active {
    border-color: var(--calc-primary);
    background: #eff6ff;
    color: var(--calc-primary);
}

.toggle-btn.oui {
    border-color: var(--calc-success);
    background: #f0fdf4;
    color: var(--calc-success);
}

/* Options de service exclusives */
.options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: var(--spacing-sm);
    margin-top: var(--spacing-md);
}

.option-card {
    border: 2px solid #e5e7eb;
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    cursor: pointer;
    transition: all 0.2s ease;
    background: white;
    text-align: center;
    min-height: 80px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.option-card:hover {
    border-color: var(--calc-primary);
    box-shadow: var(--shadow-md);
}

.option-card.selected {
    border-color: var(--calc-primary);
    background: #eff6ff;
}

.option-card input[type="radio"] {
    display: none;
}

.option-title {
    font-weight: 600;
    margin-bottom: var(--spacing-xs);
}

.option-desc {
    font-size: 0.75rem;
    color: var(--calc-gray);
}

.option-price {
    font-weight: 700;
    color: var(--calc-primary);
    margin-top: var(--spacing-xs);
}

/* Résultats sticky */
.results-panel {
    background: white;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    border: 1px solid #e5e7eb;
    position: sticky;
    top: 100px;
    height: fit-content;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
}

.results-content {
    padding: var(--spacing-xl);
}

.results-empty {
    text-align: center;
    padding: var(--spacing-2xl);
    color: var(--calc-gray);
}

.results-empty .icon {
    font-size: 3rem;
    margin-bottom: var(--spacing-lg);
}

/* Résultat principal */
.result-main {
    border: 2px solid var(--calc-success);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
    background: #f0fdf4;
}

.result-carrier {
    font-weight: 700;
    font-size: 1.25rem;
    color: var(--calc-success);
    margin-bottom: var(--spacing-sm);
}

.result-price {
    font-size: 2rem;
    font-weight: 700;
    color: var(--calc-primary);
    margin-bottom: var(--spacing-sm);
}

.result-delay {
    color: var(--calc-gray);
    font-size: 0.875rem;
}

/* Alternative */
.result-alternative {
    border: 1px solid #e5e7eb;
    border-radius: var(--radius-md);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
    cursor: pointer;
    transition: all 0.2s ease;
}

.result-alternative:hover {
    box-shadow: var(--shadow-md);
}

.alt-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.alt-details {
    display: none;
    margin-top: var(--spacing-md);
    padding-top: var(--spacing-md);
    border-top: 1px solid #e5e7eb;
    font-size: 0.875rem;
    color: var(--calc-gray);
}

.alt-details.open {
    display: block;
}

/* Bouton Reset */
.btn-reset {
    width: 100%;
    padding: var(--spacing-md);
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all 0.2s ease;
    margin-top: var(--spacing-lg);
}

.btn-reset:hover {
    background: #e5e7eb;
}

/* Bouton ADR */
.btn-adr {
    background: var(--calc-warning);
    color: white;
    padding: var(--spacing-md) var(--spacing-lg);
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    margin-top: var(--spacing-lg);
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s ease;
}

.btn-adr:hover {
    background: #d97706;
    transform: translateY(-1px);
}

/* Debug toggle */
.debug-toggle {
    margin-top: var(--spacing-md);
    font-size: 0.75rem;
    color: var(--calc-gray);
    cursor: pointer;
    text-decoration: underline;
}

.debug-info {
    display: none;
    background: #f8f9fa;
    padding: var(--spacing-md);
    border-radius: var(--radius-md);
    margin-top: var(--spacing-sm);
    font-family: monospace;
    font-size: 0.75rem;
    max-height: 200px;
    overflow-y: auto;
}

.debug-info.open {
    display: block;
}

/* Responsive */
@media (max-width: 768px) {
    .calc-container {
        grid-template-columns: 1fr;
        padding: var(--spacing-lg);
        gap: var(--spacing-lg);
    }
    
    .calc-header {
        margin-bottom: var(--spacing-lg);
    }
    
    .results-panel {
        position: static;
        order: -1;
        max-height: none;
    }
    
    .options-grid {
        grid-template-columns: 1fr;
    }
    
    .toggle-group {
        grid-template-columns: 1fr;
    }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
</style>

<!-- Contenu principal -->
<main class="calc-container">
    <!-- Titre -->
    <div class="calc-header">
        <h1 class="calc-title">🧮 <?= htmlspecialchars($page_title) ?></h1>
        <p class="calc-subtitle"><?= htmlspecialchars($page_subtitle) ?></p>
    </div>
    
    <!-- Panneau formulaire progressif -->
    <div class="form-panel">
        <div class="panel-header">
            <h2 class="panel-title">Configuration de l'expédition</h2>
        </div>
        
        <!-- Navigation étapes -->
        <nav class="steps-nav">
            <button type="button" class="step-btn active" data-step="1">
                📍 Destination
            </button>
            <button type="button" class="step-btn" data-step="2">
                📦 Expédition
            </button>
            <button type="button" class="step-btn" data-step="3">
                🚀 Options
            </button>
        </nav>
        
        <div class="form-content">
            <form id="calculatorForm">
                <!-- Étape 1: Destination -->
                <div class="form-step active" data-step="1">
                    <div class="form-group">
                        <label class="form-label" for="departement">
                            Département de destination *
                        </label>
                        <input type="text" id="departement" name="departement" class="form-input" 
                               placeholder="Ex: 67, 75, 13..." maxlength="2" 
                               pattern="[0-9]{2}" title="Code département sur 2 chiffres">
                        <small style="color: #6b7280; font-size: 0.75rem;">
                            Saisissez les 2 chiffres du département (01 à 95)
                        </small>
                    </div>
                </div>

                <!-- Étape 2: Expédition -->
                <div class="form-step" data-step="2">
                    <div class="form-group">
                        <label class="form-label" for="poids">
                            Poids total (kg) *
                        </label>
                        <input type="number" id="poids" name="poids" class="form-input" 
                               min="1" max="10000" step="0.1" placeholder="Ex: 25.5">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="type">
                            Type d'expédition *
                        </label>
                        <select id="type" name="type" class="form-input">
                            <option value="colis">Colis</option>
                            <option value="palette">Palette(s)</option>
                        </select>
                    </div>

                    <div class="form-group" id="palettesGroup" style="display: none;">
                        <label class="form-label" for="palettes">
                            Nombre de palettes
                        </label>
                        <input type="number" id="palettes" name="palettes" class="form-input" 
                               min="1" max="10" value="1">
                    </div>
                    
                    <!-- ADR obligatoire -->
                    <div class="form-group">
                        <label class="form-label">Transport ADR (marchandises dangereuses) *</label>
                        <div class="toggle-group">
                            <button type="button" class="toggle-btn" data-adr="non">
                                <span>❌ Non</span>
                            </button>
                            <button type="button" class="toggle-btn" data-adr="oui">
                                <span>⚠️ Oui</span>
                            </button>
                        </div>
                        <input type="hidden" id="adr" name="adr" value="non">
                    </div>
                </div>

                <!-- Étape 3: Options -->
                <div class="form-step" data-step="3">
                    <div class="form-group">
                        <label class="form-label">Service de livraison</label>
                        <div class="options-grid">
                            <label class="option-card selected">
                                <input type="radio" name="option_sup" value="standard" checked>
                                <div class="option-title">Standard</div>
                                <div class="option-desc">Selon grille délais</div>
                                <div class="option-price">Inclus</div>
                            </label>
                            
                            <label class="option-card">
                                <input type="radio" name="option_sup" value="rdv">
                                <div class="option-title">Sur RDV</div>
                                <div class="option-desc">Prise de rendez-vous</div>
                                <div class="option-price">~8€</div>
                            </label>
                            
                            <label class="option-card">
                                <input type="radio" name="option_sup" value="premium_matin">
                                <div class="option-title">Premium</div>
                                <div class="option-desc">Garantie matin</div>
                                <div class="option-price">Selon grille</div>
                            </label>
                            
                            <label class="option-card">
                                <input type="radio" name="option_sup" value="target">
                                <div class="option-title">Date imposée</div>
                                <div class="option-desc">Date précise</div>
                                <div class="option-price">Selon grille</div>
                            </label>
                        </div>
                    </div>

                    <!-- Enlèvement séparé -->
                    <div class="form-group" style="border-top: 1px solid #e5e7eb; padding-top: 1rem; margin-top: 1.5rem;">
                        <label class="form-label">Enlèvement à votre adresse</label>
                        <div class="toggle-group">
                            <button type="button" class="toggle-btn active" data-enlevement="non">
                                <span>❌ Non inclus</span>
                            </button>
                            <button type="button" class="toggle-btn" data-enlevement="oui">
                                <span>📤 Inclure enlèvement</span>
                            </button>
                        </div>
                        <input type="hidden" id="enlevement" name="enlevement" value="non">
                    </div>

                    <!-- Reset -->
                    <button type="button" class="btn-reset" onclick="resetForm()">
                        🔄 Nouvelle recherche
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Panneau résultats sticky -->
    <div class="results-panel">
        <div class="panel-header">
            <h2 class="panel-title">Résultats</h2>
        </div>
        
        <div class="results-content">
            <div id="resultsContainer" class="results-empty">
                <div class="icon">🚚</div>
                <h3>Prêt pour le calcul</h3>
                <p>Complétez le formulaire pour comparer les tarifs</p>
            </div>
        </div>
    </div>
</main>

<!-- JavaScript -->
<script>
// État du formulaire
let currentStep = 1;
let maxStep = 3;
let canCalculate = false;
let calculationTimeout = null;
let lastCalculationParams = null;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    loadSavedData();
});

function setupEventListeners() {
    // Navigation entre étapes
    document.querySelectorAll('.step-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const step = parseInt(this.dataset.step);
            if (step <= getMaxAccessibleStep()) {
                goToStep(step);
            }
        });
    });
    
    // Département - validation temps réel
    document.getElementById('departement').addEventListener('input', function() {
        const value = this.value.trim();
        
        if (value.length === 2 && /^(0[1-9]|[1-8][0-9]|9[0-5])$/.test(value)) {
            this.classList.add('valid');
            this.classList.remove('invalid');
            markStepCompleted(1);
            setTimeout(() => goToStep(2), 300); // Passage automatique
        } else if (value.length >= 2) {
            this.classList.add('invalid');
            this.classList.remove('valid');
        } else {
            this.classList.remove('valid', 'invalid');
        }
        
        checkCanCalculate();
    });
    
    // Poids - délai avant validation
    document.getElementById('poids').addEventListener('input', function() {
        clearTimeout(calculationTimeout);
        
        calculationTimeout = setTimeout(() => {
            const value = parseFloat(this.value);
            
            if (value > 0 && value <= 10000) {
                this.classList.add('valid');
                this.classList.remove('invalid');
                
                // Règle métier : > 60kg = palette automatique
                if (value > 60) {
                    document.getElementById('type').value = 'palette';
                    togglePalettesGroup();
                }
                
                markStepCompleted(2);
                setTimeout(() => goToStep(3), 300); // Passage automatique
            } else if (value <= 0 || value > 10000) {
                this.classList.add('invalid');
                this.classList.remove('valid');
            }
            
            checkCanCalculate();
        }, 800); // Délai pour laisser saisir 2-3 chiffres
    });
    
    // Type d'expédition
    document.getElementById('type').addEventListener('change', function() {
        togglePalettesGroup();
        checkCanCalculate();
    });
    
    // Boutons ADR tactiles
    document.querySelectorAll('[data-adr]').forEach(btn => {
        btn.addEventListener('click', function() {
            const value = this.dataset.adr;
            
            // Mise à jour visuelle
            document.querySelectorAll('[data-adr]').forEach(b => b.classList.remove('active', 'oui'));
            this.classList.add('active');
            if (value === 'oui') this.classList.add('oui');
            
            // Mise à jour input hidden
            document.getElementById('adr').value = value;
            
            checkCanCalculate();
        });
    });
    
    // Boutons enlèvement tactiles
    document.querySelectorAll('[data-enlevement]').forEach(btn => {
        btn.addEventListener('click', function() {
            const value = this.dataset.enlevement;
            
            // Mise à jour visuelle
            document.querySelectorAll('[data-enlevement]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Mise à jour input hidden
            document.getElementById('enlevement').value = value;
            
            triggerCalculation();
        });
    });
    
    // Options de service exclusives
    document.querySelectorAll('.option-card').forEach(card => {
        card.addEventListener('click', function() {
            // Retirer sélection précédente
            document.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
            // Ajouter sélection actuelle
            this.classList.add('selected');
            // Cocher le radio
            this.querySelector('input[type="radio"]').checked = true;
            
            triggerCalculation();
        });
    });
}

function goToStep(step) {
    if (step < 1 || step > maxStep) return;
    
    // Masquer toutes les étapes
    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.step-btn').forEach(s => s.classList.remove('active'));
    
    // Afficher l'étape cible
    document.querySelector(`[data-step="${step}"]`).classList.add('active');
    document.querySelector(`.step-btn[data-step="${step}"]`).classList.add('active');
    
    currentStep = step;
    
    // Focus sur le premier champ
    setTimeout(() => {
        const firstInput = document.querySelector(`[data-step="${step}"] input, [data-step="${step}"] select`);
        if (firstInput) firstInput.focus();
    }, 100);
}

function markStepCompleted(step) {
    document.querySelector(`.step-btn[data-step="${step}"]`).classList.add('completed');
}

function getMaxAccessibleStep() {
    // Étape 1 toujours accessible
    if (!isStepValid(1)) return 1;
    
    // Étape 2 si département OK
    if (!isStepValid(2)) return 2;
    
    // Étape 3 si poids OK
    return 3;
}

function isStepValid(step) {
    switch (step) {
        case 1:
            const dept = document.getElementById('departement').value;
            return dept.length === 2 && /^(0[1-9]|[1-8][0-9]|9[0-5])$/.test(dept);
        case 2:
            const poids = parseFloat(document.getElementById('poids').value);
            return poids > 0 && poids <= 10000;
        default:
            return true;
    }
}

function togglePalettesGroup() {
    const type = document.getElementById('type').value;
    const palettesGroup = document.getElementById('palettesGroup');
    
    if (type === 'palette') {
        palettesGroup.style.display = 'block';
    } else {
        palettesGroup.style.display = 'none';
    }
}

function checkCanCalculate() {
    const isValid = isStepValid(1) && isStepValid(2);
    
    if (isValid && !canCalculate) {
        canCalculate = true;
        // Déclencher le calcul automatiquement après ADR
        if (currentStep >= 3) {
            triggerCalculation();
        }
    } else if (!isValid) {
        canCalculate = false;
    }
}

function triggerCalculation() {
    if (!canCalculate) return;
    
    // Éviter les calculs en double
    const params = getFormParams();
    const paramsStr = JSON.stringify(params);
    if (paramsStr === lastCalculationParams) return;
    lastCalculationParams = paramsStr;
    
    calculateRates();
}

function getFormParams() {
    return {
        departement: document.getElementById('departement').value,
        poids: document.getElementById('poids').value,
        type: document.getElementById('type').value,
        adr: document.getElementById('adr').value,
        option_sup: document.querySelector('input[name="option_sup"]:checked').value,
        enlevement: document.getElementById('enlevement').value,
        palettes: document.getElementById('palettes').value || '1'
    };
}

async function calculateRates() {
    const resultsContainer = document.getElementById('resultsContainer');
    
    // État de chargement
    resultsContainer.innerHTML = `
        <div class="results-empty">
            <div class="icon">⏳</div>
            <h3>Calcul en cours...</h3>
            <p>Comparaison des tarifs transporteurs</p>
        </div>
    `;
    
    try {
        const params = getFormParams();
        const urlParams = new URLSearchParams(params);
        
        const response = await fetch('?ajax=calculate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: urlParams.toString()
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayResults(data.results, data.best, data.params);
            saveFormData();
        } else {
            throw new Error(data.error || 'Erreur de calcul');
        }
        
    } catch (error) {
        console.error('Erreur:', error);
        resultsContainer.innerHTML = `
            <div class="results-empty">
                <div class="icon">❌</div>
                <h3>Erreur de calcul</h3>
                <p>${error.message}</p>
                <button onclick="triggerCalculation()" class="btn-reset" style="margin-top: 1rem;">
                    Réessayer
                </button>
            </div>
        `;
    }
}

function displayResults(results, bestCarrier, params) {
    const resultsContainer = document.getElementById('resultsContainer');
    
    if (!results || Object.keys(results).length === 0) {
        resultsContainer.innerHTML = `
            <div class="results-empty">
                <div class="icon">😔</div>
                <h3>Aucun tarif trouvé</h3>
                <p>Vérifiez vos paramètres et réessayez</p>
            </div>
        `;
        return;
    }
    
    // Filtrer les résultats valides et les trier
    const validResults = Object.entries(results)
        .filter(([carrier, data]) => data && data.total > 0)
        .sort(([,a], [,b]) => a.total - b.total);
    
    if (validResults.length === 0) {
        resultsContainer.innerHTML = `
            <div class="results-empty">
                <div class="icon">😔</div>
                <h3>Aucun tarif disponible</h3>
                <p>Aucun transporteur ne dessert cette destination</p>
            </div>
        `;
        return;
    }
    
    const [bestCarrierKey, bestResult] = validResults[0];
    const carrierNames = {
        'xpo': 'XPO Logistics',
        'heppner': 'Heppner'
    };
    
    let html = `
        <!-- Informations recherche -->
        <div style="background: #f8fafc; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem;">
            <strong>Recherche:</strong> ${params.departement} • ${params.poids}kg • ${params.type}
            ${params.adr === 'oui' ? ' • ADR' : ''}
            ${params.enlevement === 'oui' ? ' • Enlèvement' : ''}
        </div>
        
        <!-- Meilleur résultat -->
        <div class="result-main">
            <div class="result-carrier">
                🏆 ${carrierNames[bestCarrierKey] || bestCarrierKey.toUpperCase()}
            </div>
            <div class="result-price">${bestResult.total.toFixed(2)} €</div>
            <div class="result-delay">
                ${bestResult.delais || '24-48h'}
                ${bestResult.service ? ' • ' + bestResult.service : ''}
            </div>
        </div>
    `;
    
    // Alternative si il y en a une
    if (validResults.length > 1) {
        const [altCarrierKey, altResult] = validResults[1];
        const altName = carrierNames[altCarrierKey] || altCarrierKey.toUpperCase();
        
        html += `
            <div class="result-alternative" onclick="toggleAlternative()">
                <div class="alt-summary">
                    <div>
                        <strong>${altName}</strong><br>
                        <span style="color: #3b82f6; font-weight: 600;">${altResult.total.toFixed(2)} €</span>
                    </div>
                    <div style="color: #6b7280;">
                        <span id="altToggle">👁️ Détails</span>
                    </div>
                </div>
                <div class="alt-details" id="altDetails">
                    <div>Base: ${altResult.base?.toFixed(2) || 'N/A'}€</div>
                    ${altResult.surcharges ? Object.entries(altResult.surcharges).map(([key, value]) => 
                        `<div>${key}: +${value.toFixed(2)}€</div>`
                    ).join('') : ''}
                    ${altResult.enlevement ? `<div>Enlèvement: +${altResult.enlevement.toFixed(2)}€</div>` : ''}
                    <div style="margin-top: 0.5rem; font-weight: 600;">
                        Frais représentation: ${altResult.additional_fees?.representation?.toFixed(2) || 'N/A'}€<br>
                        Gardiennage: ${altResult.additional_fees?.gardiennage_jour?.toFixed(2) || 'N/A'}€/jour
                    </div>
                </div>
            </div>
        `;
    }
    
    // Bouton ADR si nécessaire
    if (params.adr === 'oui') {
        html += `
            <a href="/adr/nouvelle-declaration" class="btn-adr">
                ⚠️ Nouvelle déclaration ADR
            </a>
        `;
    }
    
    // Frais supplémentaires pour le transporteur sélectionné
    if (bestResult.additional_fees) {
        html += `
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 0.875rem; color: #6b7280;">
                <strong>Frais additionnels éventuels (${carrierNames[bestCarrierKey]}):</strong><br>
                • Représentation (échec livraison): ${bestResult.additional_fees.representation?.toFixed(2) || 'N/A'}€<br>
                • Gardiennage: ${bestResult.additional_fees.gardiennage_jour?.toFixed(2) || 'N/A'}€/palette/jour
            </div>
        `;
    }
    
    // Prédictif Heppner si email disponible
    if (bestCarrierKey === 'heppner') {
        html += `
            <div style="margin-top: 1rem; padding: 0.75rem; background: #f0fdf4; border-radius: 0.5rem; font-size: 0.875rem;">
                💡 <strong>Service Predict Heppner:</strong><br>
                Avec votre email, suivi en temps réel +1,25€<br>
                <small>(non inclus dans le calcul)</small>
            </div>
        `;
    }
    
    // Debug discret
    html += `
        <div class="debug-toggle" onclick="toggleDebug()">
            🔧 Afficher les détails de calcul
        </div>
        <div class="debug-info" id="debugInfo">
            <pre>${JSON.stringify({results, params}, null, 2)}</pre>
        </div>
    `;
    
    resultsContainer.innerHTML = html;
}

function toggleAlternative() {
    const details = document.getElementById('altDetails');
    const toggle = document.getElementById('altToggle');
    
    if (details.classList.contains('open')) {
        details.classList.remove('open');
        toggle.textContent = '👁️ Détails';
    } else {
        details.classList.add('open');
        toggle.textContent = '❌ Masquer';
    }
}

function toggleDebug() {
    const debug = document.getElementById('debugInfo');
    debug.classList.toggle('open');
}

function resetForm() {
    document.getElementById('calculatorForm').reset();
    
    // Reset état
    currentStep = 1;
    canCalculate = false;
    lastCalculationParams = null;
    
    // Reset visuel
    document.querySelectorAll('.form-input').forEach(input => {
        input.classList.remove('valid', 'invalid');
    });
    
    document.querySelectorAll('.step-btn').forEach(btn => {
        btn.classList.remove('completed');
    });
    
    // Reset boutons
    document.querySelectorAll('[data-adr]').forEach(btn => btn.classList.remove('active', 'oui'));
    document.querySelector('[data-adr="non"]').classList.add('active');
    document.getElementById('adr').value = 'non';
    
    document.querySelectorAll('[data-enlevement]').forEach(btn => btn.classList.remove('active'));
    document.querySelector('[data-enlevement="non"]').classList.add('active');
    document.getElementById('enlevement').value = 'non';
    
    // Reset options
    document.querySelectorAll('.option-card').forEach(card => card.classList.remove('selected'));
    document.querySelector('.option-card').classList.add('selected');
    document.querySelector('input[name="option_sup"]').checked = true;
    
    // Masquer palettes
    document.getElementById('palettesGroup').style.display = 'none';
    
    // Retour étape 1
    goToStep(1);
    
    // Reset résultats
    document.getElementById('resultsContainer').innerHTML = `
        <div class="results-empty">
            <div class="icon">🚚</div>
            <h3>Prêt pour le calcul</h3>
            <p>Complétez le formulaire pour comparer les tarifs</p>
        </div>
    `;
    
    // Supprimer données sauvegardées
    localStorage.removeItem('calculateur_data');
}

function saveFormData() {
    const data = getFormParams();
    localStorage.setItem('calculateur_data', JSON.stringify(data));
}

function loadSavedData() {
    const saved = localStorage.getItem('calculateur_data');
    if (!saved) return;
    
    try {
        const data = JSON.parse(saved);
        
        // Charger les données
        Object.entries(data).forEach(([key, value]) => {
            const element = document.getElementById(key);
            if (element) {
                element.value = value;
                
                // Déclencher les événements pour mise à jour visuelle
                if (key === 'departement' || key === 'poids') {
                    element.dispatchEvent(new Event('input'));
                } else if (key === 'type') {
                    element.dispatchEvent(new Event('change'));
                }
            }
        });
        
        // Boutons ADR/enlèvement
        if (data.adr) {
            document.querySelectorAll('[data-adr]').forEach(btn => btn.classList.remove('active', 'oui'));
            const adrBtn = document.querySelector(`[data-adr="${data.adr}"]`);
            if (adrBtn) {
                adrBtn.classList.add('active');
                if (data.adr === 'oui') adrBtn.classList.add('oui');
            }
        }
        
        if (data.enlevement) {
            document.querySelectorAll('[data-enlevement]').forEach(btn => btn.classList.remove('active'));
            const enlBtn = document.querySelector(`[data-enlevement="${data.enlevement}"]`);
            if (enlBtn) enlBtn.classList.add('active');
        }
        
        // Options
        if (data.option_sup) {
            document.querySelectorAll('.option-card').forEach(card => card.classList.remove('selected'));
            const optionCard = document.querySelector(`input[name="option_sup"][value="${data.option_sup}"]`)?.closest('.option-card');
            if (optionCard) {
                optionCard.classList.add('selected');
                optionCard.querySelector('input').checked = true;
            }
        }
        
    } catch (e) {
        console.warn('Erreur chargement données sauvegardées');
    }
}

// Raccourcis clavier
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        resetForm();
    }
});
</script>

<?php
// Inclure le footer avec templates
include __DIR__ . '/../../templates/footer.php';
?>

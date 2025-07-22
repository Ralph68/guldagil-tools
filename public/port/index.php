<?php
/**
 * Titre: Calculateur de frais de port - VERSION FINALE PROPRE
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

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

session_start();

// ========================================
// 🔐 AUTHENTIFICATION OBLIGATOIRE
// ========================================
if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// ========================================
// 🔧 GESTION AJAX CALCULATE - SANS DONNÉES DÉMO
// ========================================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'calculate') {
    header('Content-Type: application/json');
    
    try {
        // Récupération des données POST
        parse_str(file_get_contents('php://input'), $post_data);
        
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
        ];
        
        // Validation des paramètres
        if (empty($params['departement']) || !preg_match('/^[0-9]{2,3}$/', $params['departement'])) {
            throw new Exception('Département invalide');
        }
        if ($params['poids'] <= 0 || $params['poids'] > 32000) {
            throw new Exception('Poids invalide (1-32000 kg)');
        }
        
        // Chargement de la classe Transport
        $transport_file = __DIR__ . '/calculs/transport.php';
        if (!file_exists($transport_file)) {
            throw new Exception('Classe Transport non trouvée: ' . $transport_file);
        }
        
        require_once $transport_file;
        
        if (!class_exists('Transport')) {
            throw new Exception('Classe Transport non chargée');
        }
        
        // Initialisation avec la connexion DB
        $transport = new Transport($db);
        
        // Calcul des tarifs
        $start_time = microtime(true);
        $results = $transport->calculateAll($params);
        $calc_time = round((microtime(true) - $start_time) * 1000, 2);
        
        // Formatage de la réponse - ADAPTATION AU FORMAT RETOURNÉ
        $response = [
            'success' => true,
            'carriers' => [],
            'time_ms' => $calc_time,
            'debug' => [
                'params_received' => $params,
                'raw_results' => $results
            ]
        ];
        
        // Traitement des résultats selon le format retourné
        if (isset($results['results']) && is_array($results['results'])) {
            foreach ($results['results'] as $carrier => $result) {
                if ($result !== null) {
                    if (is_numeric($result)) {
                        // Format simple : juste un prix HT
                        $prix_ht = round(floatval($result), 2);
                        $prix_ttc = round($prix_ht * 1.2, 2); // TVA 20%
                        
                        $response['carriers'][$carrier] = [
                            'prix_ht' => $prix_ht,
                            'prix_ttc' => $prix_ttc,
                            'delai' => '24-48h',
                            'service' => 'Standard'
                        ];
                    } elseif (is_array($result)) {
                        // Format complexe : tableau avec détails
                        $prix_ht = round(floatval($result['prix_ht'] ?? $result['prix'] ?? 0), 2);
                        $prix_ttc = round(floatval($result['prix_ttc'] ?? $prix_ht * 1.2), 2);
                        
                        $response['carriers'][$carrier] = [
                            'prix_ht' => $prix_ht,
                            'prix_ttc' => $prix_ttc,
                            'delai' => $result['delai'] ?? '24-48h',
                            'service' => $result['service'] ?? 'Standard'
                        ];
                    }
                }
            }
        }
        
        // Validation des résultats
        $valid_results = array_filter($response['carriers'], function($result) {
            return isset($result['prix_ttc']) && $result['prix_ttc'] > 0;
        });
        
        if (empty($valid_results)) {
            $response['carriers']['info'] = [
                'prix_ht' => 0,
                'prix_ttc' => 0,
                'delai' => 'N/A',
                'service' => 'Information',
                'message' => 'Aucun transporteur disponible pour ces critères'
            ];
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        $error_response = [
            'success' => false,
            'error' => $e->getMessage(),
            'debug' => [
                'error_file' => basename($e->getFile()),
                'error_line' => $e->getLine(),
                'post_data' => $post_data ?? null
            ]
        ];
        
        echo json_encode($error_response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ========================================
// 🎨 CHARGEMENT HEADER
// ========================================
include ROOT_PATH . '/templates/header.php';
?>

<!-- Styles intégrés pour éviter les dépendances -->
<style>
:root {
    --port-primary: #2563eb;
    --port-bg: #f8fafc;
    --port-panel: #ffffff;
    --port-border: #e2e8f0;
    --port-text: #64748b;
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

.calc-form-content, .calc-results-content {
    padding: 2rem;
}

.calc-step-content {
    display: none;
}

.calc-step-content.active {
    display: block;
}

.calc-form-group {
    margin-bottom: 1.5rem;
}

.calc-label {
    display: block;
    font-weight: 600;
    color: var(--port-text);
    margin-bottom: 0.5rem;
}

.calc-input, .calc-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--port-border);
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: all 0.2s;
    box-sizing: border-box;
}

.calc-input:focus, .calc-select:focus {
    outline: none;
    border-color: var(--port-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.calc-help {
    font-size: 0.85rem;
    color: var(--port-text);
    margin-top: 0.25rem;
    opacity: 0.8;
}

.calc-toggle-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.calc-toggle-btn {
    padding: 0.75rem 1rem;
    border: 2px solid var(--port-border);
    background: white;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    font-weight: 500;
    text-align: center;
    color: var(--port-text);
}

.calc-toggle-btn:hover {
    border-color: #60a5fa;
    background: rgba(37, 99, 235, 0.05);
}

.calc-toggle-btn.active {
    border-color: var(--port-primary);
    background: var(--port-primary);
    color: white;
}

.calc-btn-primary {
    width: 100%;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, var(--port-primary) 0%, #1d4ed8 100%);
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.2s;
}

.calc-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.calc-results-header {
    padding: 1.5rem 2rem;
    background: #f1f5f9;
    border-bottom: 1px solid var(--port-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.calc-results-header h2 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--port-text);
}

.calc-status {
    font-size: 0.9rem;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    background: rgba(6, 182, 212, 0.1);
    color: #0c5460;
}

.calc-welcome {
    text-align: center;
    color: var(--port-text);
}

.calc-welcome-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.calc-results-grid {
    display: grid;
    gap: 1rem;
    margin-top: 1rem;
}

.calc-result-card {
    border: 2px solid var(--port-border);
    border-radius: 0.75rem;
    padding: 1.5rem;
    transition: all 0.2s;
    background: white;
}

.calc-result-card:hover {
    border-color: var(--port-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.calc-result-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.calc-result-header strong {
    font-size: 1.1rem;
    color: var(--port-primary);
    font-weight: 700;
}

.calc-result-delay {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.85rem;
    font-weight: 600;
}

.calc-result-price {
    font-size: 1.8rem;
    font-weight: 800;
    color: #1d4ed8;
    text-align: center;
    margin: 0.5rem 0;
}

.calc-result-price-ht {
    text-align: center;
    font-size: 0.9rem;
    color: var(--port-text);
    opacity: 0.8;
}

/* Debug panel simple */
.debug-panel {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 300px;
    background: white;
    border: 1px solid var(--port-border);
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    font-size: 12px;
    max-height: 200px;
    overflow: hidden;
}

.debug-header {
    background: var(--port-primary);
    color: white;
    padding: 8px 12px;
    cursor: pointer;
    font-weight: 600;
}

.debug-content {
    padding: 10px;
    max-height: 150px;
    overflow-y: auto;
    display: none;
}

.debug-panel.expanded .debug-content {
    display: block;
}

@media (max-width: 1024px) {
    .calc-main {
        grid-template-columns: 1fr;
    }
    
    .calc-header h1 {
        font-size: 2rem;
    }
    
    .debug-panel {
        bottom: 10px;
        right: 10px;
        left: 10px;
        width: auto;
    }
}
</style>

<div class="calc-container">
    <main class="calc-main">
        <!-- EN-TÊTE -->
        <div class="calc-header">
            <h1>🚛 Calculateur de Frais de Port</h1>
            <p>Comparaison instantanée des tarifs XPO, Heppner et Kuehne+Nagel</p>
        </div>

        <!-- FORMULAIRE -->
        <section class="calc-form-panel">
            <div class="calc-steps">
                <button type="button" class="calc-step-btn active" data-step="1">📍 Destination</button>
                <button type="button" class="calc-step-btn" data-step="2">📦 Colis</button>
                <button type="button" class="calc-step-btn" data-step="3">⚙️ Options</button>
            </div>
            
            <div class="calc-form-content">
                <form id="calculatorForm" class="calc-form" novalidate>
                    <!-- Étape 1: Destination -->
                    <div class="calc-step-content active" data-step="1">
                        <div class="calc-form-group">
                            <label for="departement" class="calc-label">📍 Département de destination *</label>
                            <input type="text" id="departement" name="departement" class="calc-input" 
                                   placeholder="Ex: 75, 69, 13, 2A..." maxlength="3" required>
                            <small class="calc-help">Saisissez le numéro du département (01-95, 2A, 2B)</small>
                        </div>
                    </div>

                    <!-- Étape 2: Poids et Type -->
                    <div class="calc-step-content" data-step="2">
                        <div class="calc-form-group">
                            <label for="poids" class="calc-label">⚖️ Poids total de l'envoi *</label>
                            <div style="position: relative;">
                                <input type="number" id="poids" name="poids" class="calc-input" 
                                       placeholder="150" min="1" max="3000" step="0.1" required>
                                <span style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--port-text); font-weight: 600;">kg</span>
                            </div>
                            <small class="calc-help">Type suggéré automatiquement selon le poids</small>
                        </div>

                        <div class="calc-form-group">
                            <label for="type" class="calc-label">📦 Type d'envoi *</label>
                            <select id="type" name="type" class="calc-select" required>
                                <option value="">-- Sélection automatique --</option>
                                <option value="colis">📦 Colis (≤ 150kg)</option>
                                <option value="palette">🏗️ Palette (> 150kg)</option>
                            </select>
                        </div>

                        <div class="calc-form-group" id="palettesGroup" style="display: none;">
                            <label for="palettes" class="calc-label">🏗️ Nombre de palettes</label>
                            <select id="palettes" name="palettes" class="calc-select">
                                <option value="1">1 palette</option>
                                <option value="2">2 palettes</option>
                                <option value="3">3 palettes</option>
                            </select>
                        </div>

                        <div class="calc-form-group" id="paletteEurGroup" style="display: none;">
                            <label for="palette_eur" class="calc-label">🔄 Palettes EUR consignées</label>
                            <select id="palette_eur" name="palette_eur" class="calc-select">
                                <option value="0">Aucune</option>
                                <option value="1">1 palette EUR</option>
                                <option value="2">2 palettes EUR</option>
                                <option value="3">3 palettes EUR</option>
                            </select>
                        </div>
                    </div>

                    <!-- Étape 3: Options -->
                    <div class="calc-step-content" data-step="3">
                        <div class="calc-form-group">
                            <label class="calc-label">⚠️ Matières dangereuses (ADR) *</label>
                            <div class="calc-toggle-group">
                                <button type="button" class="calc-toggle-btn" data-adr="non">✅ Non - Standard</button>
                                <button type="button" class="calc-toggle-btn" data-adr="oui">⚠️ Oui - ADR</button>
                            </div>
                            <input type="hidden" id="adr" name="adr" value="">
                        </div>

                        <div class="calc-form-group">
                            <label class="calc-label">🚚 Type de service</label>
                            <div class="calc-toggle-group">
                                <button type="button" class="calc-toggle-btn active" data-enlevement="non">📮 Livraison</button>
                                <button type="button" class="calc-toggle-btn" data-enlevement="oui">🚚 Enlèvement</button>
                            </div>
                            <input type="hidden" id="enlevement" name="enlevement" value="non">
                        </div>

                        <div class="calc-form-group">
                            <label for="option_sup" class="calc-label">⚙️ Options supplémentaires</label>
                            <select id="option_sup" name="option_sup" class="calc-select">
                                <option value="standard">Standard</option>
                                <option value="express">Express</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <div class="calc-form-group">
                            <button type="submit" id="calculateBtn" class="calc-btn-primary">🧮 Calculer les tarifs</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- RÉSULTATS -->
        <section class="calc-results-panel">
            <div class="calc-results-header">
                <h2>📊 Résultats de calcul</h2>
                <div id="calcStatus" class="calc-status">⏳ En attente...</div>
            </div>
            
            <div id="resultsContent" class="calc-results-content">
                <div class="calc-welcome">
                    <div class="calc-welcome-icon">🚛</div>
                    <h3>Calculateur Intelligent</h3>
                    <p>Flow automatique avec sélection intelligente et calcul réel</p>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- Panel de debug simple -->
<div class="debug-panel" id="debugPanel">
    <div class="debug-header" onclick="this.parentElement.classList.toggle('expanded')">
        🔧 Debug
    </div>
    <div class="debug-content" id="debugContent">
        <div>Prêt pour le debug...</div>
    </div>
</div>

<!-- JavaScript pour le flow intelligent -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🧮 Calculateur initialisé - Version propre');
    
    // Cache DOM
    const dom = {
        form: document.getElementById('calculatorForm'),
        departement: document.getElementById('departement'),
        poids: document.getElementById('poids'),
        type: document.getElementById('type'),
        adr: document.getElementById('adr'),
        enlevement: document.getElementById('enlevement'),
        resultsContent: document.getElementById('resultsContent'),
        calcStatus: document.getElementById('calcStatus'),
        stepBtns: document.querySelectorAll('.calc-step-btn'),
        stepContents: document.querySelectorAll('.calc-step-content'),
        debugContent: document.getElementById('debugContent')
    };
    
    let currentStep = 1;
    let userInteracting = false;
    let lastProgressTime = 0;
    
    // Debug helper
    function addDebug(message, data = null) {
        const timestamp = new Date().toLocaleTimeString();
        const debugDiv = document.createElement('div');
        debugDiv.innerHTML = `<strong>${timestamp}:</strong> ${message}`;
        if (data) {
            debugDiv.innerHTML += `<br><pre style="font-size: 10px; margin: 2px 0;">${JSON.stringify(data, null, 2)}</pre>`;
        }
        dom.debugContent.appendChild(debugDiv);
        dom.debugContent.scrollTop = dom.debugContent.scrollHeight;
    }
    
    addDebug('Module initialisé');
    
    // Gestion des étapes
    function activateStep(step) {
        const now = Date.now();
        if (now - lastProgressTime < 500) return; // Anti-spam
        
        lastProgressTime = now;
        currentStep = step;
        
        addDebug(`Activation étape ${step}`);
        
        dom.stepBtns.forEach(btn => {
            const btnStep = parseInt(btn.dataset.step);
            btn.classList.toggle('active', btnStep === step);
        });
        
        dom.stepContents.forEach(content => {
            const contentStep = parseInt(content.dataset.step);
            if (contentStep === step) {
                content.style.display = 'block';
                content.classList.add('active');
            } else {
                content.style.display = 'none';
                content.classList.remove('active');
            }
        });
        
        // Focus sur le premier champ
        setTimeout(() => {
            if (step === 1 && dom.departement) dom.departement.focus();
            if (step === 2 && dom.poids) dom.poids.focus();
            if (step === 3 && dom.type) dom.type.focus();
        }, 300);
    }
    
    // Navigation manuelle entre étapes
    dom.stepBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const step = parseInt(btn.dataset.step);
            activateStep(step);
        });
    });
    
    // Auto-progression INTELLIGENTE
    function smartProgress() {
        if (userInteracting) return;
        
        const deptValid = validateDepartement();
        const poidsValid = validatePoids();
        
        if (deptValid && currentStep === 1) {
            addDebug('Auto-progression: Étape 1 → 2');
            setTimeout(() => activateStep(2), 800);
        } else if (deptValid && poidsValid && currentStep === 2) {
            addDebug('Auto-progression: Étape 2 → 3');
            setTimeout(() => activateStep(3), 800);
        }
    }
    
    // Validation département
    function validateDepartement() {
        if (!dom.departement) return false;
        const value = dom.departement.value.trim();
        return /^(0[1-9]|[1-8][0-9]|9[0-5]|2[AB])$/i.test(value);
    }
    
    // Validation poids
    function validatePoids() {
        if (!dom.poids) return false;
        const value = parseFloat(dom.poids.value);
        return value >= 1 && value <= 3000 && !isNaN(value);
    }
    
    // Auto-sélection type par poids
    function autoSelectType() {
        if (!dom.poids || !dom.type) return;
        
        const poids = parseFloat(dom.poids.value);
        if (isNaN(poids) || poids <= 0) return;
        
        const suggestedType = poids <= 150 ? 'colis' : 'palette';
        dom.type.value = suggestedType;
        handleTypeChange();
        
        addDebug(`Auto-sélection: ${poids}kg → ${suggestedType}`);
    }
    
    // Gestion type palette/colis
    function handleTypeChange() {
        const type = dom.type.value;
        const palettesGroup = document.getElementById('palettesGroup');
        const paletteEurGroup = document.getElementById('paletteEurGroup');
        
        if (palettesGroup) palettesGroup.style.display = type === 'palette' ? 'block' : 'none';
        if (paletteEurGroup) paletteEurGroup.style.display = type === 'palette' ? 'block' : 'none';
    }
    
    // Events département
    if (dom.departement) {
        dom.departement.addEventListener('focus', () => { userInteracting = true; });
        dom.departement.addEventListener('blur', () => { 
            userInteracting = false; 
            setTimeout(smartProgress, 200);
        });
        dom.departement.addEventListener('input', () => {
            if (validateDepartement()) {
                setTimeout(smartProgress, 300);
            }
        });
    }
    
    // Events poids
    if (dom.poids) {
        dom.poids.addEventListener('focus', () => { userInteracting = true; });
        dom.poids.addEventListener('blur', () => { 
            userInteracting = false; 
            setTimeout(smartProgress, 200);
        });
        dom.poids.addEventListener('input', () => {
            autoSelectType();
            if (validatePoids()) {
                setTimeout(smartProgress, 300);
            }
        });
    }
    
    // Events type
    if (dom.type) {
        dom.type.addEventListener('change', handleTypeChange);
    }
    
    // Gestion toggles ADR
    document.querySelectorAll('[data-adr]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-adr]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            dom.adr.value = this.dataset.adr;
            
            const isAdr = this.dataset.adr === 'oui';
            addDebug(`ADR sélectionné: ${isAdr ? 'OUI' : 'NON'}`);
            
            // Animation des boutons ADR
            this.style.animation = 'pulse 0.5s ease-in-out';
            setTimeout(() => { this.style.animation = ''; }, 500);
            
            // Calcul automatique après délai
            setTimeout(() => {
                if (validateForm()) {
                    addDebug('Lancement calcul automatique après ADR');
                    handleCalculate();
                }
            }, 1500);
        });
    });
    
    // Gestion toggles enlèvement
    document.querySelectorAll('[data-enlevement]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-enlevement]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            dom.enlevement.value = this.dataset.enlevement;
            
            addDebug(`Enlèvement: ${this.dataset.enlevement}`);
        });
    });
    
    // Validation formulaire
    function validateForm() {
        const dept = dom.departement.value.trim();
        const poids = parseFloat(dom.poids.value);
        const type = dom.type.value;
        
        const deptValid = /^(0[1-9]|[1-8][0-9]|9[0-5]|2[AB])$/i.test(dept);
        const poidsValid = poids >= 1 && poids <= 3000 && !isNaN(poids);
        const typeValid = type !== '';
        
        return deptValid && poidsValid && typeValid;
    }
    
    // Calcul principal
    async function handleCalculate() {
        if (!validateForm()) {
            addDebug('Validation échouée');
            return;
        }
        
        dom.calcStatus.textContent = '⏳ Calcul en cours...';
        addDebug('Début calcul');
        
        const formData = new FormData(dom.form);
        const params = Object.fromEntries(formData.entries());
        
        addDebug('Paramètres envoyés', params);
        
        try {
            const response = await fetch('?ajax=calculate', {
                method: 'POST',
                body: new URLSearchParams(params)
            });
            
            const data = await response.json();
            addDebug('Réponse reçue', data);
            
            if (data.success) {
                displayResults(data);
                dom.calcStatus.textContent = '✅ Calcul terminé';
            } else {
                throw new Error(data.error || 'Erreur inconnue');
            }
        } catch (error) {
            console.error('Erreur calcul:', error);
            addDebug('ERREUR', error.message);
            dom.calcStatus.textContent = '❌ Erreur: ' + error.message;
        }
    }
    
    // Affichage résultats
    function displayResults(data) {
        let html = '<div class="calc-results-wrapper">';
        html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid var(--port-border);">';
        html += '<h3 style="margin: 0; color: var(--port-primary); font-size: 1.3rem;">🚛 Résultats de calcul</h3>';
        html += '<small style="color: var(--port-text); font-weight: 500;">Calculé en ' + (data.time_ms || 0) + 'ms</small>';
        html += '</div>';
        
        if (data.carriers && Object.keys(data.carriers).length > 0) {
            html += '<div class="calc-results-grid">';
            
            Object.entries(data.carriers).forEach(([carrier, result]) => {
                const carrierNames = {
                    'xpo': 'XPO Logistics',
                    'heppner': 'Heppner',
                    'kn': 'Kuehne + Nagel',
                    'info': 'Information'
                };
                
                const name = carrierNames[carrier] || carrier.toUpperCase();
                const prixTTC = result.prix_ttc || 0;
                const prixHT = result.prix_ht || 0;
                const delai = result.delai || 'N/A';
                
                html += '<div class="calc-result-card">';
                html += '<div class="calc-result-header">';
                html += '<strong>' + name + '</strong>';
                if (delai !== 'N/A') {
                    html += '<span class="calc-result-delay">' + delai + '</span>';
                }
                html += '</div>';
                
                if (prixTTC > 0) {
                    html += '<div class="calc-result-price">' + prixTTC.toFixed(2) + ' € TTC</div>';
                    if (prixHT > 0 && prixHT !== prixTTC) {
                        html += '<div class="calc-result-price-ht">HT: ' + prixHT.toFixed(2) + ' €</div>';
                    }
                } else {
                    html += '<div style="text-align: center; color: var(--port-text); font-style: italic;">';
                    html += result.message || 'Non disponible';
                    html += '</div>';
                }
                
                html += '</div>';
            });
            
            html += '</div>';
        } else {
            html += '<div style="text-align: center; padding: 3rem 2rem; color: var(--port-text);">';
            html += '<p style="font-size: 1.1rem; margin: 0;">⚠️ Aucun résultat disponible</p>';
            html += '</div>';
        }
        
        html += '</div>';
        
        dom.resultsContent.innerHTML = html;
        addDebug('Résultats affichés');
    }
    
    // Soumission formulaire
    if (dom.form) {
        dom.form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleCalculate();
        });
    }
    
    // Bouton calcul
    const calculateBtn = document.getElementById('calculateBtn');
    if (calculateBtn) {
        calculateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handleCalculate();
        });
    }
    
    // Gestion clavier
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey && e.target.matches('input, select')) {
            e.preventDefault();
            
            const deptValid = validateDepartement();
            const poidsValid = validatePoids();
            
            if (currentStep === 1 && deptValid) {
                activateStep(2);
            } else if (currentStep === 2 && deptValid && poidsValid) {
                activateStep(3);
            } else if (currentStep >= 3 && validateForm()) {
                handleCalculate();
            }
        }
    });
    
    addDebug('Tous les événements configurés');
});

// Styles CSS pour les animations
const style = document.createElement('style');
style.textContent = `
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.calc-form.loading {
    opacity: 0.7;
    pointer-events: none;
}

.calc-toggle-btn:hover {
    transform: translateY(-1px);
}

.calc-step-btn:hover {
    color: var(--port-primary);
}

.calc-result-card:hover {
    border-color: var(--port-primary);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.1);
}

.calc-input:focus, .calc-select:focus {
    transform: translateY(-1px);
}

/* Responsive améliorer */
@media (max-width: 768px) {
    .calc-steps {
        flex-direction: column;
    }
    
    .calc-step-btn {
        border-bottom: 1px solid var(--port-border);
        border-right: none;
    }
    
    .calc-step-btn:last-child {
        border-bottom: none;
    }
    
    .calc-toggle-group {
        grid-template-columns: 1fr;
    }
    
    .calc-header h1 {
        font-size: 1.8rem;
    }
    
    .calc-results-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}
`;
document.head.appendChild(style);
</script>

<?php
// ========================================
// 🎨 CHARGEMENT FOOTER
// ========================================
include ROOT_PATH . '/templates/footer.php';
?>

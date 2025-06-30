<?php
/**
 * Titre: Calculateur de frais de port - Interface complète
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

$version_info = getVersionInfo();
$page_title = 'Calculateur de Frais de Port';
session_start();

// GESTION AJAX CALCULATE
if (isset($_GET['ajax']) && $_GET['ajax'] === 'calculate') {
    header('Content-Type: application/json');
    
    try {
        // Lecture données POST
        parse_str(file_get_contents('php://input'), $post_data);
        
        // Paramètres normalisés EXACTEMENT comme attendu par features/port/transport.php
        $params = [
            'departement' => str_pad(trim($post_data['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
            'poids' => floatval($post_data['poids'] ?? 0),
            'type' => strtolower(trim($post_data['type'] ?? 'colis')),
            'adr' => (($post_data['adr'] ?? 'non') === 'oui'),
            'option_sup' => trim($post_data['option_sup'] ?? 'standard'),
            'enlevement' => ($post_data['enlevement'] ?? 'non') === 'oui',
            'palettes' => max(1, intval($post_data['palettes'] ?? 1)),
        ];
        
        // Validation
        if (empty($params['departement']) || !preg_match('/^[0-9]{2,3}$/', $params['departement'])) {
            throw new Exception('Département invalide');
        }
        if ($params['poids'] <= 0 || $params['poids'] > 32000) {
            throw new Exception('Poids invalide');
        }
        
        // Chargement Transport - CHEMIN EXACT
        $transport_file = __DIR__ . '/../../features/port/transport.php';
        if (!file_exists($transport_file)) {
            throw new Exception('Transport non trouvé: ' . $transport_file);
        }
        
        require_once $transport_file;
        $transport = new Transport($db);
        
        // Appel calculateAll avec array
        $start_time = microtime(true);
        $results = $transport->calculateAll($params);
        $calc_time = round((microtime(true) - $start_time) * 1000, 2);
        
        // Format réponse
        $response = [
            'success' => true,
            'carriers' => [],
            'time_ms' => $calc_time,
            'debug' => $transport->debug ?? null
        ];
        
        $carrier_names = [
            'xpo' => 'XPO Logistics',
            'heppner' => 'Heppner',
            'kn' => 'Kuehne + Nagel'
        ];
        
        // Traitement résultats
        $carrier_results = $results['results'] ?? $results;
        
        foreach ($carrier_results as $carrier => $price) {
            $response['carriers'][$carrier] = [
                'name' => $carrier_names[$carrier] ?? strtoupper($carrier),
                'price' => $price,
                'formatted' => $price ? number_format($price, 2, ',', ' ') . ' €' : 'Non disponible',
                'available' => $price !== null && $price > 0
            ];
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'debug' => $transport->debug ?? null
        ]);
    }
    exit;
}

// GESTION AJAX DELAY
if (isset($_GET['ajax']) && $_GET['ajax'] === 'delay') {
    header('Content-Type: application/json');
    
    $carrier = $_GET['carrier'] ?? '';
    $dept = $_GET['dept'] ?? '';
    $option = $_GET['option'] ?? 'standard';
    
    try {
        $table_map = [
            'xpo' => 'gul_xpo_rates',
            'heppner' => 'gul_heppner_rates', 
            'kn' => 'gul_kn_rates'
        ];
        
        if (isset($table_map[$carrier])) {
            $sql = "SELECT delais FROM {$table_map[$carrier]} WHERE num_departement = ? LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$dept]);
            $row = $stmt->fetch();
            
            $delay = $row['delais'] ?? '24-48h';
            
            switch ($option) {
                case 'premium_matin': $delay .= ' garanti avant 13h'; break;
                case 'rdv': $delay .= ' sur RDV'; break;
                case 'target': $delay = 'Date imposée'; break;
            }
        } else {
            $delay = '24-48h';
        }
        
        echo json_encode(['success' => true, 'delay' => $delay]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'delay' => '24-48h']);
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>
    
    <style>
        :root {
            --primary: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-900: #111827;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: start;
        }
        
        @media (max-width: 768px) {
            .main-grid { grid-template-columns: 1fr; }
        }
        
        /* FORMULAIRE */
        .form-panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .steps-nav {
            display: flex;
            margin-bottom: 25px;
            background: var(--gray-100);
            border-radius: 10px;
            padding: 4px;
        }
        
        .step-btn {
            flex: 1;
            padding: 12px 8px;
            border: none;
            background: transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .step-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }
        
        .step-btn.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .step-indicator {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--gray-300);
            color: var(--gray-600);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .step-btn.active .step-indicator {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        
        .step-btn.completed .step-indicator {
            background: var(--success);
            color: white;
        }
        
        .form-step {
            display: none;
        }
        
        .form-step.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--gray-700);
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-input.valid {
            border-color: var(--success);
        }
        
        .toggle-group {
            display: flex;
            gap: 8px;
        }
        
        .toggle-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid var(--gray-200);
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .toggle-btn.active {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .option-card {
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .option-card:has(input:checked) {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .option-card input {
            display: none;
        }
        
        .option-title {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .option-desc {
            font-size: 12px;
            color: var(--gray-500);
            margin-bottom: 4px;
        }
        
        .option-price {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .btn-calculate {
            width: 100%;
            padding: 16px;
            background: var(--success);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-calculate:hover {
            background: #059669;
        }
        
        .btn-reset {
            padding: 10px 20px;
            background: var(--gray-100);
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            cursor: pointer;
            margin-top: 15px;
        }
        
        /* RÉSULTATS */
        .results-panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .results-header {
            margin-bottom: 20px;
        }
        
        .calculation-status {
            font-size: 14px;
            color: var(--gray-500);
        }
        
        .results-grid {
            display: grid;
            gap: 15px;
        }
        
        .carrier-card {
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            padding: 16px;
            transition: all 0.2s;
        }
        
        .carrier-card.available {
            border-color: var(--success);
        }
        
        .carrier-card.unavailable {
            opacity: 0.6;
        }
        
        .carrier-name {
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .carrier-price {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--success);
        }
        
        .carrier-delay {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 4px;
        }
        
        /* HISTORIQUE ET DEBUG */
        .history-section, .debug-container {
            margin-top: 20px;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .history-header, .debug-header {
            background: var(--gray-700);
            color: white;
            padding: 12px 16px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .history-content, .debug-content {
            padding: 16px;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }
        
        .debug-carrier {
            margin-bottom: 15px;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
        }
        
        .debug-carrier-header {
            background: var(--gray-50);
            padding: 10px 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
        }
        
        .debug-carrier-content {
            padding: 12px;
            font-family: monospace;
            font-size: 13px;
            display: none;
        }
        
        .debug-step {
            margin-bottom: 5px;
            padding: 4px 8px;
            background: var(--gray-50);
            border-radius: 4px;
        }
        
        .debug-step.success { border-left: 3px solid var(--success); }
        .debug-step.error { border-left: 3px solid var(--error); }
        
        .debug-json {
            background: var(--gray-900);
            color: #00ff00;
            padding: 10px;
            border-radius: 4px;
            white-space: pre-wrap;
            font-size: 11px;
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚛 Calculateur de Frais de Port</h1>
            <p>Comparez instantanément XPO, Heppner et Kuehne+Nagel</p>
        </div>
        
        <div class="main-grid">
            <!-- FORMULAIRE -->
            <div class="form-panel">
                <nav class="steps-nav">
                    <button type="button" class="step-btn active" data-step="1">
                        <span class="step-indicator">1</span>
                        📍 Destination
                    </button>
                    <button type="button" class="step-btn disabled" data-step="2">
                        <span class="step-indicator">2</span>
                        📦 Expédition
                    </button>
                    <button type="button" class="step-btn disabled" data-step="3">
                        <span class="step-indicator">3</span>
                        🚀 Options
                    </button>
                </nav>
                
                <form id="calculatorForm">
                    <!-- Étape 1: Destination -->
                    <div class="form-step active" data-step="1">
                        <div class="form-group">
                            <label class="form-label" for="departement">Département de destination *</label>
                            <input type="text" id="departement" name="departement" class="form-input" 
                                   placeholder="Ex: 67, 75, 13..." maxlength="3" required>
                        </div>
                    </div>
                    
                    <!-- Étape 2: Expédition -->
                    <div class="form-step" data-step="2">
                        <div class="form-group">
                            <label class="form-label" for="poids">Poids total (kg) *</label>
                            <input type="number" id="poids" name="poids" class="form-input" 
                                   min="0.1" max="32000" step="0.1" placeholder="Ex: 25.5" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="type">Type d'expédition *</label>
                            <select id="type" name="type" class="form-input" required>
                                <option value="">Choisir...</option>
                                <option value="colis">Colis</option>
                                <option value="palette">Palette(s) EUR</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="palettesGroup" style="display: none;">
                            <label class="form-label" for="palettes">Nombre de palettes EUR</label>
                            <input type="number" id="palettes" name="palettes" class="form-input" 
                                   min="1" max="20" value="1">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Transport ADR *</label>
                            <div class="toggle-group">
                                <button type="button" class="toggle-btn active" data-adr="non">❌ Non</button>
                                <button type="button" class="toggle-btn" data-adr="oui">⚠️ Oui</button>
                            </div>
                            <input type="hidden" id="adr" name="adr" value="non">
                        </div>
                    </div>
                    
                    <!-- Étape 3: Options -->
                    <div class="form-step" data-step="3">
                        <div class="form-group">
                            <label class="form-label">Service de livraison</label>
                            <div class="options-grid">
                                <label class="option-card">
                                    <input type="radio" name="option_sup" value="standard" checked>
                                    <div class="option-title">Standard</div>
                                    <div class="option-desc">Selon grille délais</div>
                                    <div class="option-price">Inclus</div>
                                </label>
                                
                                <label class="option-card">
                                    <input type="radio" name="option_sup" value="rdv">
                                    <div class="option-title">Sur RDV</div>
                                    <div class="option-desc">Prise de rendez-vous</div>
                                    <div class="option-price">~12€</div>
                                </label>
                                
                                <label class="option-card">
                                    <input type="radio" name="option_sup" value="premium_matin">
                                    <div class="option-title">Premium</div>
                                    <div class="option-desc">Garantie matin</div>
                                    <div class="option-price">~15€</div>
                                </label>
                                
                                <label class="option-card">
                                    <input type="radio" name="option_sup" value="target">
                                    <div class="option-title">Date imposée</div>
                                    <div class="option-desc">Date précise</div>
                                    <div class="option-price">~25€</div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Enlèvement à votre adresse</label>
                            <div class="toggle-group">
                                <button type="button" class="toggle-btn active" data-enlevement="non">❌ Non</button>
                                <button type="button" class="toggle-btn" data-enlevement="oui">📤 Oui</button>
                            </div>
                            <input type="hidden" id="enlevement" name="enlevement" value="non">
                        </div>
                        
                        <button type="submit" class="btn-calculate">🧮 Calculer les tarifs</button>
                        <button type="button" class="btn-reset" onclick="resetForm()">🔄 Nouvelle recherche</button>
                    </div>
                </form>
            </div>
            
            <!-- RÉSULTATS -->
            <div class="results-panel">
                <div class="results-header">
                    <h2>💰 Tarifs</h2>
                    <div class="calculation-status" id="calcStatus">⏳ En attente...</div>
                </div>
                
                <div class="results-content" id="resultsContent">
                    <div style="text-align: center; padding: 40px; color: var(--gray-500);">
                        <div style="font-size: 48px; margin-bottom: 10px;">🧮</div>
                        <p>Complétez le formulaire pour voir les tarifs</p>
                    </div>
                </div>
                
                <!-- Historique -->
                <div class="history-section" id="historySection" style="display: none;">
                    <div class="history-header" onclick="toggleHistory()">
                        <span>📋 Historique des calculs</span>
                        <span id="historyToggle">▼</span>
                    </div>
                    <div class="history-content" id="historyContent">
                        <p style="text-align: center; color: var(--gray-500);">Aucun calcul dans l'historique</p>
                    </div>
                </div>
                
                <!-- Debug -->
                <div class="debug-container" id="debugContainer" style="display: none;">
                    <div class="debug-header" onclick="toggleDebug()">
                        <span>🐛 Debug Transport</span>
                        <span id="debugToggle">▼</span>
                    </div>
                    <div class="debug-content" id="debugContent"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let currentStep = 1;
        
        // Navigation séquentielle
        function activateStep(step) {
            document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.step-btn').forEach(el => {
                el.classList.remove('active');
                el.classList.add('disabled');
            });
            
            for (let i = 1; i <= step; i++) {
                const stepEl = document.querySelector(`.form-step[data-step="${i}"]`);
                const btnEl = document.querySelector(`.step-btn[data-step="${i}"]`);
                const indicator = btnEl.querySelector('.step-indicator');
                
                if (i < step) {
                    btnEl.classList.remove('disabled');
                    btnEl.classList.add('completed');
                    indicator.textContent = '✓';
                } else if (i === step) {
                    stepEl.classList.add('active');
                    btnEl.classList.add('active');
                    btnEl.classList.remove('disabled');
                    indicator.textContent = i;
                }
            }
            currentStep = step;
        }
        
        // Validation et passage automatique
        document.getElementById('departement').addEventListener('input', function() {
            if (this.value.length >= 2 && /^[0-9]+$/.test(this.value)) {
                this.classList.add('valid');
                setTimeout(() => {
                    activateStep(2);
                    document.getElementById('poids').focus();
                }, 300);
            } else {
                this.classList.remove('valid');
            }
        });
        
        document.getElementById('poids').addEventListener('input', function() {
            if (parseFloat(this.value) > 0) {
                this.classList.add('valid');
                setTimeout(() => {
                    document.getElementById('type').focus();
                }, 100);
            }
        });
        
        document.getElementById('type').addEventListener('change', function() {
            if (this.value) {
                this.classList.add('valid');
                document.getElementById('palettesGroup').style.display = 
                    this.value === 'palette' ? 'block' : 'none';
                setTimeout(() => activateStep(3), 300);
            }
        });
        
        // Gestion toggles
        document.querySelectorAll('[data-adr]').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('[data-adr]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('adr').value = this.dataset.adr;
            });
        });
        
        document.querySelectorAll('[data-enlevement]').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('[data-enlevement]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('enlevement').value = this.dataset.enlevement;
            });
        });
        
        // Soumission formulaire
        document.getElementById('calculatorForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const params = Object.fromEntries(formData.entries());
            
            document.getElementById('calcStatus').textContent = '⏳ Calcul en cours...';
            
            try {
                const response = await fetch('?ajax=calculate', {
                    method: 'POST',
                    body: new URLSearchParams(params)
                });
                
                const data = await response.json();
                displayResults(data);
                
                if (data.debug) {
                    displayDebug(data);
                }
                
            } catch (error) {
                console.error('Erreur:', error);
                document.getElementById('calcStatus').textContent = '❌ Erreur de calcul';
            }
        });
        
        function displayResults(data) {
            const content = document.getElementById('resultsContent');
            const status = document.getElementById('calcStatus');
            
            if (!data.success) {
                status.textContent = '❌ ' + (data.error || 'Erreur de calcul');
                return;
            }
            
            status.textContent = `✅ Calculé en ${data.time_ms}ms`;
            
            let html = '<div class="results-grid">';
            
            Object.entries(data.carriers).forEach(([carrier, info]) => {
                const cardClass = info.available ? 'available' : 'unavailable';
                html += `
                    <div class="carrier-card ${cardClass}">
                        <div class="carrier-name">${info.name}</div>
                        <div class="carrier-price">${info.formatted}</div>
                        <div class="carrier-delay" id="delay-${carrier}">Calcul délai...</div>
                    </div>
                `;
                
                // Récupération délai
                if (info.available) {
                    fetchDelay(carrier);
                }
            });
            
            html += '</div>';
            content.innerHTML = html;
            
            // Afficher historique
            document.getElementById('historySection').style.display = 'block';
        }
        
        function displayDebug(data) {
            if (!data.debug) return;
            
            const container = document.getElementById('debugContainer');
            const content = document.getElementById('debugContent');
            
            let html = '';
            
            // Paramètres
            if (data.debug.params_normalized) {
                html += `
                    <div class="debug-json">${JSON.stringify(data.debug.params_normalized, null, 2)}</div>
                `;
            }
            
            // Debug par transporteur
            ['xpo', 'heppner', 'kn'].forEach(carrier => {
                if (data.debug[carrier]) {
                    const carrierData = data.debug[carrier];
                    
                    html += `
                        <div class="debug-carrier">
                            <div class="debug-carrier-header" onclick="toggleCarrierDebug('${carrier}')">
                                <span>🚛 ${carrier.toUpperCase()}</span>
                                <span id="carrier-toggle-${carrier}">▼</span>
                            </div>
                            <div class="debug-carrier-content" id="carrier-content-${carrier}">
                    `;
                    
                    if (carrierData.steps) {
                        carrierData.steps.forEach(step => {
                            const stepClass = step.includes('✓') ? 'success' : 'error';
                            html += `<div class="debug-step ${stepClass}">${step}</div>`;
                        });
                    }
                    
                    if (carrierData.constraint_checks) {
                        html += `
                            <div style="margin: 8px 0; font-weight: bold;">Vérifications contraintes:</div>
                            <div class="debug-json">${JSON.stringify(carrierData.constraint_checks, null, 2)}</div>
                        `;
                    }
                    
                    if (carrierData.tariff_lookup) {
                        html += `
                            <div style="margin: 8px 0; font-weight: bold;">Recherche tarif BDD:</div>
                            <div class="debug-json">${JSON.stringify(carrierData.tariff_lookup, null, 2)}</div>
                        `;
                    }
                    
                    if (carrierData.sql_query) {
                        html += `
                            <div style="margin: 8px 0; font-weight: bold;">Requête SQL:</div>
                            <div class="debug-json">${carrierData.sql_query}</div>
                        `;
                    }
                    
                    if (carrierData.sql_result !== undefined) {
                        html += `
                            <div style="margin: 8px 0; font-weight: bold;">Résultat SQL:</div>
                            <div class="debug-json">${JSON.stringify(carrierData.sql_result, null, 2)}</div>
                        `;
                    }
                    
                    html += `
                            </div>
                        </div>
                    `;
                }
            });
            
            content.innerHTML = html;
            container.style.display = 'block';
        }
        
        async function fetchDelay(carrier) {
            try {
                const dept = document.getElementById('departement').value;
                const option = document.querySelector('input[name="option_sup"]:checked')?.value || 'standard';
                
                const response = await fetch(`?ajax=delay&carrier=${carrier}&dept=${dept}&option=${option}`);
                const data = await response.json();
                
                const delayEl = document.getElementById(`delay-${carrier}`);
                if (delayEl && data.success) {
                    delayEl.textContent = data.delay;
                }
            } catch (error) {
                console.error('Erreur délai:', error);
            }
        }
        
        function toggleHistory() {
            const content = document.getElementById('historyContent');
            const toggle = document.getElementById('historyToggle');
            
            if (content.style.display === 'block') {
                content.style.display = 'none';
                toggle.textContent = '▼';
            } else {
                content.style.display = 'block';
                toggle.textContent = '▲';
            }
        }
        
        function toggleDebug() {
            const content = document.getElementById('debugContent');
            const toggle = document.getElementById('debugToggle');
            
            if (content.style.display === 'block') {
                content.style.display = 'none';
                toggle.textContent = '▼';
            } else {
                content.style.display = 'block';
                toggle.textContent = '▲';
            }
        }
        
        function toggleCarrierDebug(carrier) {
            const content = document.getElementById(`carrier-content-${carrier}`);
            const toggle = document.getElementById(`carrier-toggle-${carrier}`);
            
            if (content.style.display === 'block') {
                content.style.display = 'none';
                toggle.textContent = '▼';
            } else {
                content.style.display = 'block';
                toggle.textContent = '▲';
            }
        }
        
        function resetForm() {
            document.getElementById('calculatorForm').reset();
            document.getElementById('adr').value = 'non';
            document.getElementById('enlevement').value = 'non';
            document.getElementById('palettesGroup').style.display = 'none';
            
            document.querySelectorAll('[data-adr]').forEach(btn => btn.classList.remove('active'));
            document.querySelector('[data-adr="non"]').classList.add('active');
            
            document.querySelectorAll('[data-enlevement]').forEach(btn => btn.classList.remove('active'));
            document.querySelector('[data-enlevement="non"]').classList.add('active');
            
            document.getElementById('resultsContent').innerHTML = `
                <div style="text-align: center; padding: 40px; color: var(--gray-500);">
                    <div style="font-size: 48px; margin-bottom: 10px;">🧮</div>
                    <p>Complétez le formulaire pour voir les tarifs</p>
                </div>
            `;
            
            document.getElementById('calcStatus').textContent = '⏳ En attente...';
            document.getElementById('historySection').style.display = 'none';
            document.getElementById('debugContainer').style.display = 'none';
            
            activateStep(1);
            document.getElementById('departement').focus();
        }
        
        // Navigation par clic sur étapes
        document.querySelectorAll('.step-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!this.classList.contains('disabled')) {
                    const step = parseInt(this.dataset.step);
                    activateStep(step);
                }
            });
        });
        
        // Initialisation
        activateStep(1);
        document.getElementById('departement').focus();
    </script>
</body>
</html>
<?php
// Inclure le footer avec templates
include __DIR__ . '/../../templates/footer.php';
?>

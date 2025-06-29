<?php
/**
 * Titre: Calculateur de frais de port - Interface corrigée
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

// 1. Afficher toutes les erreurs pour éviter la page blanche
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Chargement configuration (doit définir $db)
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

$version_info = getVersionInfo();
$page_title = 'Calculateur de Frais de Port';
session_start();
$user_authenticated = true;

// Fonctions utilitaires
$debug_info = [];
$calculation_time = 0;
$results = null;
$validation_errors = [];

function validateCalculatorData(array $data): array {
    $errors = [];
    
    if (empty($data['departement']) || !preg_match('/^(0[1-9]|[1-8][0-9]|9[0-5])$/', $data['departement'])) {
        $errors['departement'] = 'Département invalide (01-95)';
    }
    
    if (empty($data['poids']) || $data['poids'] <= 0 || $data['poids'] > 32000) {
        $errors['poids'] = 'Poids invalide (0.1 - 32000 kg)';
    }
    
    return $errors;
}

// 3. Gestion AJAX
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    // 3.1 Calcul des tarifs
    if ($_GET['ajax'] === 'calculate') {
        parse_str(file_get_contents('php://input'), $_POST);
        
        // Préparation des paramètres
        $params = [
            'departement' => str_pad(trim($_POST['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
            'poids' => floatval($_POST['poids'] ?? 0),
            'type' => strtolower(trim($_POST['type'] ?? 'colis')),
            'adr' => (($_POST['adr'] ?? 'non') === 'oui'),
            'option_sup' => trim($_POST['option_sup'] ?? 'standard'),
            'enlevement' => isset($_POST['enlevement']),
            'palettes' => max(0, intval($_POST['palettes'] ?? 0)),
        ];

        $errors = validateCalculatorData($params);
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        try {
            // Charger la classe Transport depuis le bon chemin
            $transport_file = __DIR__ . '/../../features/port/transport.php';
            if (!file_exists($transport_file)) {
                throw new \Exception('Service de calcul introuvable: ' . $transport_file);
            }
            require_once $transport_file;
            
            if (!class_exists('Transport')) {
                throw new \Exception('Classe Transport non trouvée');
            }
            
            $transport = new Transport($db);
            $start = microtime(true);
            $results = $transport->calculateAll($params);
            $calculation_time = round((microtime(true) - $start) * 1000, 2);

            // Format réponse compatible
            $response = [
                'success' => true,
                'carriers' => [],
                'time_ms' => $calculation_time
            ];

            // Noms des transporteurs
            $carrier_names = [
                'xpo' => 'XPO Logistics',
                'heppner' => 'Heppner',
                'kn' => 'Kuehne+Nagel'
            ];

            // Traitement des résultats selon structure
            $carrier_results = [];
            if (isset($results['results'])) {
                $carrier_results = $results['results'];
            } else {
                $carrier_results = $results;
            }

            foreach ($carrier_results as $carrier => $price) {
                if ($price !== null && $price > 0) {
                    $response['carriers'][] = [
                        'carrier_code' => $carrier,
                        'carrier_name' => $carrier_names[$carrier] ?? strtoupper($carrier),
                        'price' => $price,
                        'price_display' => number_format($price, 2, ',', ' ') . '€',
                        'options' => [
                            'rdv_cost' => $this->getRDVCost($carrier),
                            'enlevement_cost' => $this->getEnlevementCost($carrier),
                            'premium_cost' => $this->getPremiumCost($carrier)
                        ]
                    ];
                }
            }

            echo json_encode($response);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Action AJAX inconnue']);
    exit;
}

// Méthodes pour coûts options
function getRDVCost($carrier) {
    $costs = ['heppner' => 6.70, 'xpo' => 7.00, 'kn' => 8.00];
    return $costs[$carrier] ?? 7.00;
}

function getEnlevementCost($carrier) {
    $costs = ['heppner' => 0.00, 'xpo' => 25.00, 'kn' => 20.00];
    return $costs[$carrier] ?? 15.00;
}

function getPremiumCost($carrier) {
    return 25.00; // Standard premium
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Guldagil</title>
    
    <style>
        /* Variables */
        :root {
            --primary: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --gray: #6b7280;
            --light-gray: #f8fafc;
            --xpo-color: #e11d48;
            --heppner-color: #10b981;
            --kn-color: #3b82f6;
        }
        
        /* Reset */
        * { box-sizing: border-box; }
        body { 
            margin: 0; 
            font-family: -apple-system, sans-serif; 
            background: var(--light-gray);
            font-size: 14px;
        }
        
        /* Layout compact */
        .calc-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 1rem;
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 1.5rem;
        }
        
        /* Header compact */
        .calc-header {
            background: linear-gradient(135deg, var(--primary), #2563eb);
            color: white;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }
        
        .calc-header h1 {
            margin: 0;
            font-size: 1.25rem;
        }
        
        .calc-header p {
            margin: 0.25rem 0 0;
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        /* Formulaire compact */
        .calc-form {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .calc-form-header {
            background: var(--primary);
            color: white;
            padding: 1rem;
            border-radius: 8px 8px 0 0;
        }
        
        .calc-form-header h2 {
            margin: 0;
            font-size: 1rem;
        }
        
        .calc-form-content {
            padding: 1rem;
        }
        
        .calc-form-group {
            margin-bottom: 1rem;
        }
        
        .calc-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
            font-size: 0.875rem;
        }
        
        .calc-input, .calc-select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        
        .calc-input:focus, .calc-select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .calc-help {
            font-size: 0.75rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }
        
        /* Options */
        .calc-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .calc-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        .calc-option:hover {
            background: #f8fafc;
        }
        
        /* Bouton */
        .calc-button {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
        }
        
        .calc-button:hover {
            background: #1d4ed8;
        }
        
        .calc-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Résultats */
        .calc-results {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .calc-results-header {
            background: var(--primary);
            color: white;
            padding: 1rem;
            border-radius: 8px 8px 0 0;
        }
        
        .calc-results-header h2 {
            margin: 0;
            font-size: 1rem;
        }
        
        .calc-results-content {
            padding: 1rem;
            min-height: 300px;
        }
        
        .calc-empty-state {
            text-align: center;
            color: var(--gray);
            padding: 2rem 1rem;
        }
        
        .calc-empty-state .icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        /* Loading */
        .calc-loading {
            text-align: center;
            padding: 1.5rem;
            color: var(--primary);
        }
        
        .calc-spinner {
            width: 30px;
            height: 30px;
            border: 3px solid #e5e7eb;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Cartes transporteurs */
        .calc-result-card {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            position: relative;
        }
        
        .calc-result-card.best {
            border-color: var(--success);
            background: #f0fdf4;
        }
        
        .calc-result-card[data-carrier="xpo"] {
            border-left: 3px solid var(--xpo-color);
        }
        
        .calc-result-card[data-carrier="heppner"] {
            border-left: 3px solid var(--heppner-color);
        }
        
        .calc-result-card[data-carrier="kn"] {
            border-left: 3px solid var(--kn-color);
        }
        
        .calc-result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .calc-transporteur {
            font-weight: 600;
            font-size: 0.9rem;
            color: #374151;
        }
        
        .calc-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .calc-result-card.best .calc-price {
            color: var(--success);
        }
        
        .calc-details {
            font-size: 0.8rem;
            color: var(--gray);
            line-height: 1.4;
        }
        
        .calc-options-detail {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.75rem;
        }
        
        .calc-option-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.25rem;
        }
        
        /* Messages d'erreur */
        .calc-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        /* Badge meilleur prix */
        .best-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--success);
            color: white;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-weight: 600;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .calc-container {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="calc-header">
    <h1><?= htmlspecialchars($page_title) ?></h1>
    <p>Comparaison XPO, Heppner, Kuehne+Nagel</p>
</div>

<!-- Interface principale -->
<div class="calc-container">
    <!-- Formulaire -->
    <div class="calc-form">
        <div class="calc-form-header">
            <h2>📋 Paramètres</h2>
        </div>
        
        <div class="calc-form-content">
            <form id="calcForm">
                <div class="calc-form-group">
                    <label for="departement" class="calc-label">Département *</label>
                    <input type="text" id="departement" class="calc-input" placeholder="67" maxlength="2" required>
                    <div class="calc-help">Code département (01-95)</div>
                </div>
                
                <div class="calc-form-group">
                    <label for="poids" class="calc-label">Poids (kg) *</label>
                    <input type="number" id="poids" class="calc-input" placeholder="25.5" min="0.1" step="0.1" required>
                    <div class="calc-help">Poids total</div>
                </div>
                
                <div class="calc-form-group">
                    <label for="type" class="calc-label">Type</label>
                    <select id="type" class="calc-select">
                        <option value="colis">Colis</option>
                        <option value="palette">Palette</option>
                    </select>
                </div>
                
                <div class="calc-form-group" id="palettesGroup" style="display: none;">
                    <label for="palettes" class="calc-label">Nb palettes EUR</label>
                    <input type="number" id="palettes" class="calc-input" placeholder="1" min="0" max="10" value="0">
                    <div class="calc-help">Palettes Europe consignées</div>
                </div>
                
                <div class="calc-form-group">
                    <label class="calc-label">Matières dangereuses</label>
                    <div class="calc-options">
                        <label class="calc-option">
                            <input type="radio" name="adr" value="non" checked>
                            <span>Non ADR</span>
                        </label>
                        <label class="calc-option">
                            <input type="radio" name="adr" value="oui">
                            <span>ADR</span>
                        </label>
                    </div>
                </div>
                
                <div class="calc-form-group">
                    <label for="option_sup" class="calc-label">Service</label>
                    <select id="option_sup" class="calc-select">
                        <option value="standard">Standard</option>
                        <option value="rdv">Prise RDV</option>
                        <option value="premium_matin">Premium matin</option>
                        <option value="target">Date imposée</option>
                    </select>
                </div>
                
                <div class="calc-form-group">
                    <label class="calc-option">
                        <input type="checkbox" id="enlevement">
                        <span>Enlèvement extérieur</span>
                    </label>
                    <div class="calc-help">Collecte hors siège</div>
                </div>
                
                <button type="submit" class="calc-button" id="calculateBtn">
                    🧮 Calculer
                </button>
            </form>
        </div>
    </div>
    
    <!-- Résultats -->
    <div class="calc-results">
        <div class="calc-results-header">
            <h2>💰 Tarifs</h2>
        </div>
        
        <div class="calc-results-content" id="resultsContent">
            <div class="calc-empty-state">
                <div class="icon">📊</div>
                <p><strong>Prêt pour le calcul</strong></p>
                <p>Remplissez le formulaire pour comparer les tarifs</p>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('calcForm');
    const typeSelect = document.getElementById('type');
    const palettesGroup = document.getElementById('palettesGroup');
    const calculateBtn = document.getElementById('calculateBtn');
    const resultsContent = document.getElementById('resultsContent');
    
    // Gestion palette
    typeSelect.addEventListener('change', function() {
        if (this.value === 'palette') {
            palettesGroup.style.display = 'block';
        } else {
            palettesGroup.style.display = 'none';
        }
    });
    
    // Auto-palette si > 60kg
    document.getElementById('poids').addEventListener('input', function() {
        if (parseFloat(this.value) > 60) {
            typeSelect.value = 'palette';
            palettesGroup.style.display = 'block';
        }
    });
    
    // Validation département
    document.getElementById('departement').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
    });
    
    // Soumission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const departement = document.getElementById('departement').value;
        const poids = parseFloat(document.getElementById('poids').value);
        
        if (!departement || departement.length !== 2) {
            showError('Département invalide (01-95)');
            return;
        }
        
        if (!poids || poids <= 0) {
            showError('Poids invalide');
            return;
        }
        
        calculateTariffs();
    });
    
    function showError(message) {
        resultsContent.innerHTML = `<div class="calc-error">❌ ${message}</div>`;
    }
    
    function showLoading() {
        calculateBtn.disabled = true;
        calculateBtn.textContent = 'Calcul...';
        
        resultsContent.innerHTML = `
            <div class="calc-loading">
                <div class="calc-spinner"></div>
                <p>Calcul en cours...</p>
            </div>
        `;
    }
    
    function calculateTariffs() {
        showLoading();
        
        const formData = new URLSearchParams();
        formData.append('departement', document.getElementById('departement').value);
        formData.append('poids', document.getElementById('poids').value);
        formData.append('type', document.getElementById('type').value);
        formData.append('adr', document.querySelector('input[name="adr"]:checked').value);
        formData.append('option_sup', document.getElementById('option_sup').value);
        formData.append('palettes', document.getElementById('palettes').value);
        if (document.getElementById('enlevement').checked) {
            formData.append('enlevement', '1');
        }
        
        fetch('?ajax=calculate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            calculateBtn.disabled = false;
            calculateBtn.textContent = '🧮 Calculer';
            
            if (data.success && data.carriers && data.carriers.length > 0) {
                displayResults(data);
            } else {
                showError(data.error || 'Aucun tarif disponible');
            }
        })
        .catch(error => {
            calculateBtn.disabled = false;
            calculateBtn.textContent = '🧮 Calculer';
            showError('Erreur de connexion');
            console.error('Erreur:', error);
        });
    }
    
    function displayResults(data) {
        const carriers = data.carriers.sort((a, b) => a.price - b.price);
        let html = '';
        
        carriers.forEach((carrier, index) => {
            const isBest = index === 0;
            const options = getSelectedOptions();
            
            html += `
                <div class="calc-result-card ${isBest ? 'best' : ''}" data-carrier="${carrier.carrier_code}">
                    ${isBest ? '<div class="best-badge">🏆 MEILLEUR</div>' : ''}
                    <div class="calc-result-header">
                        <div class="calc-transporteur">${carrier.carrier_name}</div>
                        <div class="calc-price">${carrier.price_display}</div>
                    </div>
                    <div class="calc-details">
                        Tarif TTC • Délai: ${getDelay(carrier.carrier_code, options)}
                    </div>
                    ${generateOptionsDetail(carrier, options)}
                </div>
            `;
        });
        
        html += `
            <div style="text-align: center; margin-top: 1rem; font-size: 0.8rem; color: var(--gray);">
                ⚡ Calcul: ${data.time_ms}ms • Économie: ${(carriers[carriers.length-1].price - carriers[0].price).toFixed(2)}€
            </div>
        `;
        
        resultsContent.innerHTML = html;
    }
    
    function getSelectedOptions() {
        return {
            service: document.getElementById('option_sup').value,
            enlevement: document.getElementById('enlevement').checked,
            adr: document.querySelector('input[name="adr"]:checked').value === 'oui'
        };
    }
    
    function getDelay(carrier, options) {
        const baseDelays = {
            'xpo': '24-48h',
            'heppner': '24-48h', 
            'kn': '48-72h'
        };
        
        let delay = baseDelays[carrier] || '24-48h';
        
        if (options.service === 'rdv') delay += ' sur RDV';
        if (options.service === 'premium_matin') delay += ' avant 13h';
        if (options.service === 'target') delay = 'Date imposée';
        
        return delay;
    }
    
    function generateOptionsDetail(carrier, options) {
        const optionsCosts = [];
        
        if (options.service === 'rdv') {
            const costs = {'xpo': 7.00, 'heppner': 6.70, 'kn': 8.00};
            optionsCosts.push(`RDV: +${costs[carrier.carrier_code]?.toFixed(2) || '7.00'}€`);
        }
        
        if (options.service === 'premium_matin') {
            optionsCosts.push('Premium: +25.00€');
        }
        
        if (options.service === 'target') {
            optionsCosts.push('Date imposée: +30.00€');
        }
        
        if (options.enlevement) {
            const costs = {'xpo': 25.00, 'heppner': 0.00, 'kn': 20.00};
            const cost = costs[carrier.carrier_code] || 15.00;
            if (cost > 0) {
                optionsCosts.push(`Enlèvement: +${cost.toFixed(2)}€`);
            } else {
                optionsCosts.push('Enlèvement: Gratuit');
            }
        }
        
        if (options.adr) {
            optionsCosts.push('ADR: Inclus');
        }
        
        if (optionsCosts.length === 0) {
            return '';
        }
        
        return `
            <div class="calc-options-detail">
                ${optionsCosts.map(opt => `<div class="calc-option-item"><span>${opt}</span></div>`).join('')}
            </div>
        `;
    }
});
</script>

</body>
</html>

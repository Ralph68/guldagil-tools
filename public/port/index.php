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

// DEBUG activé temporairement
define('DEBUG_CALC', true);

// Gestion AJAX avec debug
if (isset($_GET['ajax']) && $_GET['ajax'] === 'calculate') {
    header('Content-Type: application/json');
    
    $debug_info = [];
    
    try {
        // Lecture des données POST
        $input_data = file_get_contents('php://input');
        parse_str($input_data, $post_data);
        
        $debug_info['input_raw'] = $input_data;
        $debug_info['post_parsed'] = $post_data;
        
        // Paramètres normalisés
        $params = [
            'departement' => str_pad(trim($post_data['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
            'poids' => floatval($post_data['poids'] ?? 0),
            'type' => strtolower(trim($post_data['type'] ?? 'colis')),
            'adr' => (($post_data['adr'] ?? 'non') === 'oui'),
            'option_sup' => trim($post_data['option_sup'] ?? 'standard'),
            'enlevement' => isset($post_data['enlevement']) && $post_data['enlevement'] === '1',
            'palettes' => max(0, intval($post_data['palettes'] ?? 0)),
        ];
        
        $debug_info['params_normalized'] = $params;
        
        // Validation
        if (empty($params['departement']) || !preg_match('/^(0[1-9]|[1-8][0-9]|9[0-5])$/', $params['departement'])) {
            throw new Exception('Département invalide: ' . $params['departement']);
        }
        
        if ($params['poids'] <= 0 || $params['poids'] > 32000) {
            throw new Exception('Poids invalide: ' . $params['poids']);
        }
        
        // Chargement classe Transport
        $transport_file = __DIR__ . '/../../features/port/transport.php';
        $debug_info['transport_file'] = $transport_file;
        $debug_info['transport_exists'] = file_exists($transport_file);
        
        if (!file_exists($transport_file)) {
            throw new Exception('Fichier Transport non trouvé: ' . $transport_file);
        }
        
        require_once $transport_file;
        
        if (!class_exists('Transport')) {
            throw new Exception('Classe Transport non chargée');
        }
        
        // Vérification DB
        if (!isset($db) || !$db instanceof PDO) {
            throw new Exception('Base de données non disponible');
        }
        
        $debug_info['db_connected'] = true;
        
        // Test requête simple
        $test_query = $db->query("SELECT COUNT(*) as count FROM gul_xpo_rates LIMIT 1");
        $debug_info['db_test'] = $test_query ? $test_query->fetch()['count'] : 'erreur';
        
        // Calcul
        $transport = new Transport($db);
        $start_time = microtime(true);
        
        $debug_info['transport_created'] = true;
        
        // Test direct méthode
        if (!method_exists($transport, 'calculateAll')) {
            throw new Exception('Méthode calculateAll non trouvée');
        }
        
        // Test avec debug détaillé pour chaque transporteur
        $debug_info['individual_tests'] = [];
        foreach (['xpo', 'heppner'] as $carrier) {
            $debug_info['individual_tests'][$carrier] = [];
            
            // Test requête directe BDD
            try {
                $table = $carrier === 'xpo' ? 'gul_xpo_rates' : 'gul_heppner_rates';
                $sql = "SELECT COUNT(*) as count, MIN(prix_colis) as min_prix, MAX(prix_colis) as max_prix 
                        FROM {$table} WHERE num_departement = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$params['departement']]);
                $test_result = $stmt->fetch();
                
                $debug_info['individual_tests'][$carrier]['db_test'] = $test_result;
                
                // Test avec quelques enregistrements
                $sql = "SELECT * FROM {$table} WHERE num_departement = ? LIMIT 3";
                $stmt = $db->prepare($sql);
                $stmt->execute([$params['departement']]);
                $sample_records = $stmt->fetchAll();
                
                $debug_info['individual_tests'][$carrier]['sample_records'] = $sample_records;
                
            } catch (Exception $e) {
                $debug_info['individual_tests'][$carrier]['db_error'] = $e->getMessage();
            }
        }
        
        $results = $transport->calculateAll($params);
        $calc_time = round((microtime(true) - $start_time) * 1000, 2);
        
        $debug_info['results_raw'] = $results;
        $debug_info['transport_debug'] = $transport->debug ?? 'pas de debug';
        
        // Formatage réponse
        $response = [
            'success' => false,
            'carriers' => [],
            'time_ms' => $calc_time,
            'debug' => DEBUG_CALC ? $debug_info : null
        ];
        
        // Traitement résultats
        $carrier_results = isset($results['results']) ? $results['results'] : $results;
        $valid_count = 0;
        
        $carrier_names = [
            'xpo' => 'XPO Logistics',
            'heppner' => 'Heppner',
            'kn' => 'Kuehne+Nagel'
        ];
        
        foreach ($carrier_results as $carrier => $price) {
            $debug_info['carrier_' . $carrier] = [
                'raw_price' => $price,
                'is_numeric' => is_numeric($price),
                'is_positive' => $price > 0
            ];
            
            if (is_numeric($price) && $price > 0) {
                $valid_count++;
                
                // Récupération du délai depuis la BDD
                $delay = getCarrierDelay($db, $carrier, $params['departement'], $params['option_sup']);
                
                $response['carriers'][] = [
                    'carrier_code' => $carrier,
                    'carrier_name' => $carrier_names[$carrier] ?? strtoupper($carrier),
                    'price' => (float)$price,
                    'price_display' => number_format($price, 2, ',', ' ') . '€',
                    'delay' => $delay
                ];
            }
        }
        
        $response['success'] = $valid_count > 0;
        $response['message'] = $valid_count > 0 ? 
            "$valid_count transporteur(s) disponible(s)" : 
            'Aucun tarif disponible';
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'debug' => DEBUG_CALC ? ($debug_info ?? []) : null
        ], JSON_PRETTY_PRINT);
    }
    exit;
}

// Fonction pour récupérer le délai depuis la BDD
function getCarrierDelay($db, $carrier, $departement, $option_sup) {
    try {
        $table_map = [
            'xpo' => 'gul_xpo_rates',
            'heppner' => 'gul_heppner_rates',
            'kn' => 'gul_kn_rates'
        ];
        
        if (!isset($table_map[$carrier])) {
            return '24-48h';
        }
        
        $sql = "SELECT delais FROM {$table_map[$carrier]} WHERE num_departement = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$departement]);
        $row = $stmt->fetch();
        
        $delay = $row['delais'] ?? '24-48h';
        
        // Adaptation selon options
        switch ($option_sup) {
            case 'premium_matin':
                if ($carrier === 'heppner') $delay .= ' avant 13h';
                elseif ($carrier === 'xpo') $delay .= ' avant 14h';
                else $delay .= ' matin';
                break;
            case 'target':
                $delay = 'Date imposée';
                break;
            case 'rdv':
                $delay .= ' sur RDV';
                break;
        }
        
        return $delay;
        
    } catch (Exception $e) {
        return '24-48h';
    }
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
            --gray: #6b7280;
            --light-gray: #f8fafc;
        }
        
        * { box-sizing: border-box; }
        body { 
            margin: 0; 
            font-family: -apple-system, sans-serif; 
            background: var(--light-gray);
            font-size: 14px;
        }
        
        /* Header */
        .calc-header {
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: white;
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        
        /* Layout principal */
        .calc-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            padding: 2rem;
            min-height: calc(100vh - 120px);
        }
        
        /* Formulaire scrollable */
        .calc-form-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            overflow: hidden;
        }
        
        .calc-form-header {
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: white;
            padding: 1.5rem;
        }
        
        .calc-form-header h2 {
            margin: 0;
            font-size: 1.1rem;
        }
        
        .calc-form-content {
            padding: 2rem;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }
        
        /* Résultats sticky */
        .calc-results-wrapper {
            position: sticky;
            top: 120px;
            height: fit-content;
            max-height: calc(100vh - 140px);
        }
        
        .calc-results {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            overflow: hidden;
        }
        
        .calc-results-header {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            padding: 1.5rem;
        }
        
        .calc-results-header h2 {
            margin: 0;
            font-size: 1.1rem;
        }
        
        .calc-results-content {
            padding: 1.5rem;
            max-height: calc(100vh - 300px);
            overflow-y: auto;
        }
        
        /* Champs formulaire */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
            font-size: 0.9rem;
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-help {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }
        
        /* Options radio */
        .radio-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        
        .radio-option:hover {
            background: #f8fafc;
            border-color: var(--primary);
        }
        
        .radio-option input:checked + span {
            font-weight: 600;
            color: var(--primary);
        }
        
        /* Checkbox */
        .checkbox-option {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .checkbox-option:hover {
            background: #f8fafc;
            border-color: var(--primary);
        }
        
        /* Bouton principal */
        .calc-button {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 1.5rem;
        }
        
        .calc-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .calc-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* États résultats */
        .results-empty {
            text-align: center;
            color: var(--gray);
            padding: 3rem 1rem;
        }
        
        .results-empty .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .results-loading {
            text-align: center;
            padding: 2rem;
            color: var(--primary);
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Cartes transporteurs */
        .carrier-card {
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .carrier-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .carrier-card.best {
            border-color: var(--success);
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            position: relative;
        }
        
        .carrier-card.best::before {
            content: '🏆 MEILLEUR';
            position: absolute;
            top: -8px;
            right: 1rem;
            background: var(--success);
            color: white;
            font-size: 0.7rem;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .carrier-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .carrier-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #374151;
        }
        
        .carrier-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .carrier-card.best .carrier-price {
            color: var(--success);
        }
        
        .carrier-details {
            font-size: 0.85rem;
            color: var(--gray);
            line-height: 1.4;
        }
        
        /* Messages d'erreur */
        .error-message {
            background: #fef2f2;
            border: 2px solid #fecaca;
            color: #dc2626;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        /* Debug */
        .debug-panel {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.8rem;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .debug-panel pre {
            margin: 0;
            white-space: pre-wrap;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .calc-container {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 1rem;
            }
            
            .calc-results-wrapper {
                position: static;
                order: -1;
            }
            
            .radio-group {
                grid-template-columns: 1fr;
            }
        }
        
        /* Section titres */
        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #374151;
            margin: 2rem 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .section-title:first-child {
            margin-top: 0;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="calc-header">
    <h1><?= htmlspecialchars($page_title) ?></h1>
    <p>Comparaison instantanée XPO, Heppner, Kuehne+Nagel</p>
</div>

<!-- Container principal -->
<div class="calc-container">
    
    <!-- Formulaire scrollable -->
    <div class="calc-form-wrapper">
        <div class="calc-form-header">
            <h2>📋 Paramètres d'expédition</h2>
        </div>
        
        <div class="calc-form-content">
            <form id="calcForm">
                
                <h3 class="section-title">📍 Destination</h3>
                <div class="form-group">
                    <label for="departement" class="form-label">Département de destination *</label>
                    <input type="text" id="departement" class="form-input" 
                           placeholder="Ex: 67, 75, 13..." maxlength="2" required>
                    <div class="form-help">Code département français (01-95)</div>
                </div>
                
                <h3 class="section-title">⚖️ Poids et conditionnement</h3>
                <div class="form-group">
                    <label for="poids" class="form-label">Poids total (kg) *</label>
                    <input type="number" id="poids" class="form-input" 
                           placeholder="Ex: 25.5" min="0.1" step="0.1" max="32000" required>
                    <div class="form-help">Poids brut total incluant emballage</div>
                </div>
                
                <div class="form-group">
                    <label for="type" class="form-label">Type d'envoi</label>
                    <select id="type" class="form-select">
                        <option value="colis">Colis standard</option>
                        <option value="palette">Palette</option>
                    </select>
                </div>
                
                <div class="form-group" id="palettesGroup" style="display: none;">
                    <label for="palettes" class="form-label">Nombre de palettes EUR consignées</label>
                    <input type="number" id="palettes" class="form-input" 
                           placeholder="0" min="0" max="20" value="0">
                    <div class="form-help">Palettes Europe retournables (peut être 0)</div>
                </div>
                
                <h3 class="section-title">⚠️ Matières dangereuses</h3>
                <div class="form-group">
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="adr" value="non" checked>
                            <span>✅ Non ADR</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="adr" value="oui">
                            <span>⚠️ ADR</span>
                        </label>
                    </div>
                </div>
                
                <h3 class="section-title">⚙️ Options de livraison</h3>
                <div class="form-group">
                    <label for="option_sup" class="form-label">Service de livraison</label>
                    <select id="option_sup" class="form-select">
                        <option value="standard">Standard</option>
                        <option value="rdv">Prise de RDV (+6-8€)</option>
                        <option value="premium_matin">Premium matin (+25€)</option>
                        <option value="target">Date imposée (+30€)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-option">
                        <input type="checkbox" id="enlevement">
                        <span>🏭 Enlèvement extérieur (hors siège social)</span>
                    </label>
                    <div class="form-help">Gratuit Heppner, +25€ XPO, +20€ Kuehne+Nagel</div>
                </div>
                
                <button type="submit" class="calc-button" id="calculateBtn">
                    🧮 Calculer les tarifs
                </button>
                
            </form>
        </div>
    </div>
    
    <!-- Résultats sticky -->
    <div class="calc-results-wrapper">
        <div class="calc-results">
            <div class="calc-results-header">
                <h2>💰 Comparaison des tarifs</h2>
            </div>
            
            <div class="calc-results-content" id="resultsContent">
                <div class="results-empty">
                    <div class="icon">📊</div>
                    <p><strong>Prêt pour le calcul</strong></p>
                    <p>Remplissez le formulaire pour obtenir<br>une comparaison des tarifs transporteurs</p>
                </div>
            </div>
        </div>
    </div>
    
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('calcForm');
    const calculateBtn = document.getElementById('calculateBtn');
    const resultsContent = document.getElementById('resultsContent');
    const typeSelect = document.getElementById('type');
    const palettesGroup = document.getElementById('palettesGroup');
    
    // Gestion palette
    typeSelect.addEventListener('change', function() {
        palettesGroup.style.display = this.value === 'palette' ? 'block' : 'none';
    });
    
    // Auto-palette si > 60kg
    document.getElementById('poids').addEventListener('input', function() {
        if (parseFloat(this.value) > 60) {
            typeSelect.value = 'palette';
            palettesGroup.style.display = 'block';
        }
    });
    
    // Validation département temps réel
    document.getElementById('departement').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
        if (this.value.length >= 2) {
            // Calcul automatique si formulaire valide
            setTimeout(checkAutoCalculate, 500);
        }
    });
    
    // Calcul automatique sur changement
    ['poids', 'type', 'option_sup'].forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            setTimeout(checkAutoCalculate, 300);
        });
    });
    
    document.querySelectorAll('input[name="adr"]').forEach(radio => {
        radio.addEventListener('change', function() {
            setTimeout(checkAutoCalculate, 300);
        });
    });
    
    document.getElementById('enlevement').addEventListener('change', function() {
        setTimeout(checkAutoCalculate, 300);
    });
    
    function checkAutoCalculate() {
        const dept = document.getElementById('departement').value;
        const poids = parseFloat(document.getElementById('poids').value);
        
        if (dept && dept.length === 2 && poids && poids > 0) {
            calculateTariffs();
        }
    }
    
    // Soumission manuelle
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        calculateTariffs();
    });
    
    function showError(message) {
        resultsContent.innerHTML = `<div class="error-message">❌ ${message}</div>`;
    }
    
    function showLoading() {
        calculateBtn.disabled = true;
        calculateBtn.textContent = 'Calcul en cours...';
        
        resultsContent.innerHTML = `
            <div class="results-loading">
                <div class="spinner"></div>
                <p><strong>Calcul en cours...</strong></p>
                <p>Interrogation des tarifs transporteurs</p>
            </div>
        `;
    }
    
    function calculateTariffs() {
        const dept = document.getElementById('departement').value;
        const poids = parseFloat(document.getElementById('poids').value);
        
        if (!dept || dept.length !== 2) {
            showError('Département invalide (01-95)');
            return;
        }
        
        if (!poids || poids <= 0) {
            showError('Poids invalide');
            return;
        }
        
        showLoading();
        
        const formData = new URLSearchParams();
        formData.append('departement', dept);
        formData.append('poids', poids.toString());
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
            calculateBtn.textContent = '🧮 Calculer les tarifs';
            
            console.log('Réponse API:', data);
            
            if (data.success && data.carriers && data.carriers.length > 0) {
                displayResults(data);
            } else {
                showError(data.error || data.message || 'Aucun tarif disponible');
                
                // Afficher debug si disponible
                if (data.debug) {
                    const debugHtml = `
                        <div class="debug-panel">
                            <strong>Debug info:</strong>
                            <pre>${JSON.stringify(data.debug, null, 2)}</pre>
                        </div>
                    `;
                    resultsContent.innerHTML += debugHtml;
                }
            }
        })
        .catch(error => {
            calculateBtn.disabled = false;
            calculateBtn.textContent = '🧮 Calculer les tarifs';
            showError('Erreur de connexion');
            console.error('Erreur fetch:', error);
        });
    }
    
    function displayResults(data) {
        const carriers = data.carriers.sort((a, b) => a.price - b.price);
        let html = '';
        
        carriers.forEach((carrier, index) => {
            const isBest = index === 0;
            
            html += `
                <div class="carrier-card ${isBest ? 'best' : ''}">
                    <div class="carrier-header">
                        <div class="carrier-name">${carrier.carrier_name}</div>
                        <div class="carrier-price">${carrier.price_display}</div>
                    </div>
                    <div class="carrier-details">
                        Tarif TTC • Délai: ${carrier.delay || getDelay(carrier.carrier_code)}
                        ${getOptionsText(carrier.carrier_code)}
                    </div>
                </div>
            `;
        });
        
        // Métadonnées
        const economy = carriers.length > 1 ? 
            (carriers[carriers.length-1].price - carriers[0].price).toFixed(2) : '0.00';
        
        html += `
            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 0.85rem; color: var(--gray);">
                ⚡ Calcul: ${data.time_ms}ms • Économie max: ${economy}€
            </div>
        `;
        
        resultsContent.innerHTML = html;
    }
    
    function getDelay(carrier) {
        const delays = {
            'xpo': '24-48h',
            'heppner': '24-48h', 
            'kn': '48-72h'
        };
        return delays[carrier] || '24-48h';
    }
    
    function getOptionsText(carrier) {
        const service = document.getElementById('option_sup').value;
        const enlevement = document.getElementById('enlevement').checked;
        const options = [];
        
        if (service === 'rdv') options.push('RDV');
        if (service === 'premium_matin') options.push('Premium');
        if (service === 'target') options.push('Date imposée');
        if (enlevement) {
            const costs = {'xpo': '+25€', 'heppner': 'Gratuit', 'kn': '+20€'};
            options.push('Enlèvement ' + (costs[carrier] || '+15€'));
        }
        
        return options.length > 0 ? ' • ' + options.join(' • ') : '';
    }
});
</script>

</body>
</html>

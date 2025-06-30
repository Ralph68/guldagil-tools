<?php
/**
 * Titre: Calculateur de frais de port - Interface complète CORRIGÉE
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

// Fonction pour récupérer les délais depuis la BDD
function getCarrierDelay($db, $carrier, $departement, $option_sup = 'standard') {
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
        
        // Adaptation selon l'option de service
        switch ($option_sup) {
            case 'premium_matin':
                return $delay . ' garanti avant 13h';
            case 'rdv':
                return $delay . ' sur RDV';
            case 'target':
                return 'Date imposée précise';
            default:
                return $delay;
        }
        
    } catch (Exception $e) {
        return '24-48h'; // Fallback
    }
}

// Gestion AJAX avec debug amélioré
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
        
        // Chargement classe Transport avec chemin corrigé
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
        
        // Initialisation du transport
        $transport = new Transport($db);
        $start_time = microtime(true);
        
        // Appel avec la signature array (nouvelle version)
        $results = $transport->calculateAll($params);
        $calc_time = round((microtime(true) - $start_time) * 1000, 2);
        
        $debug_info['transport_call'] = 'signature array';
        $debug_info['raw_results'] = $results;
        
        // Formatage réponse compatible
        $response = [
            'success' => false,
            'carriers' => [],
            'time_ms' => $calc_time,
            'debug' => DEBUG_CALC ? $debug_info : null
        ];
        
        // Support des deux formats de retour
        $carrier_results = isset($results['results']) ? $results['results'] : $results;
        $valid_count = 0;
        
        $carrier_names = [
            'xpo' => 'XPO Logistics',
            'heppner' => 'Heppner',
            'kn' => 'Kuehne+Nagel'
        ];
        
        foreach ($carrier_results as $carrier => $price_data) {
            $debug_info['carrier_' . $carrier] = [
                'raw_data' => $price_data,
                'is_numeric' => is_numeric($price_data),
                'is_positive' => is_numeric($price_data) && $price_data > 0
            ];
            
            // Support prix direct ou structure complexe
            $price = null;
            if (is_numeric($price_data) && $price_data > 0) {
                $price = (float)$price_data;
            } elseif (is_array($price_data) && isset($price_data['price']) && $price_data['price'] > 0) {
                $price = (float)$price_data['price'];
            }
            
            if ($price !== null && $price > 0) {
                $valid_count++;
                
                // Récupération délai depuis BDD
                $delay = getCarrierDelay($db, $carrier, $params['departement'], $params['option_sup']);
                
                $response['carriers'][] = [
                    'carrier_code' => $carrier,
                    'carrier_name' => $carrier_names[$carrier] ?? strtoupper($carrier),
                    'price' => $price,
                    'price_display' => number_format($price, 2, ',', ' ') . '€',
                    'delay' => $delay
                ];
            }
        }
        
        $response['success'] = $valid_count > 0;
        $response['message'] = $valid_count > 0 ? 
            "$valid_count transporteur(s) disponible(s)" : 
            'Aucun tarif disponible pour cette destination';
        
        // Debug des résultats finaux
        if (DEBUG_CALC) {
            $response['debug']['valid_count'] = $valid_count;
            $response['debug']['final_carriers'] = $response['carriers'];
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'debug' => DEBUG_CALC ? ($debug_info ?? []) : null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - <?= APP_NAME ?></title>
    <!-- CSS intégré pour éviter les erreurs MIME -->
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray: #6b7280;
            --gray-light: #f3f4f6;
            --white: #ffffff;
            --border: #e5e7eb;
        }

        .calculator-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
        }

        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-calculate {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
        }

        .btn-calculate:hover {
            transform: translateY(-2px);
        }

        .btn-calculate:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .results-container {
            margin-top: 2rem;
            padding: 1.5rem;
            background: var(--gray-light);
            border-radius: 8px;
            display: none;
        }

        .results-container.show {
            display: block;
        }

        .carrier-result {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background: var(--white);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .carrier-result.best {
            border-left-color: var(--success);
            background: #f0fdf4;
        }

        .carrier-info {
            display: flex;
            flex-direction: column;
        }

        .carrier-name {
            font-weight: 600;
            color: #1f2937;
        }

        .carrier-delay {
            font-size: 0.875rem;
            color: var(--gray);
        }

        .carrier-price {
            text-align: right;
        }

        .price-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
        }

        .price-badge {
            background: var(--success);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--border);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .debug-info {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.875rem;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="header" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 2rem; text-align: center; margin-bottom: 2rem;">
        <h1 style="margin: 0; font-size: 2rem;"><?= htmlspecialchars($page_title) ?></h1>
        <div class="version-info" style="opacity: 0.9; margin-top: 0.5rem;">
            Version <?= APP_VERSION ?> - Build <?= BUILD_NUMBER ?>
        </div>
    </div>

    <div class="calculator-container">
        <form id="calc-form" onsubmit="return false;">
            <div class="form-grid">
                <div class="form-group">
                    <label for="departement">Département de destination *</label>
                    <input type="text" id="departement" name="departement" 
                           placeholder="Ex: 67, 75, 13..." maxlength="2" required>
                </div>

                <div class="form-group">
                    <label for="poids">Poids (kg) *</label>
                    <input type="number" id="poids" name="poids" 
                           min="0.1" max="32000" step="0.1" placeholder="Ex: 25.5" required>
                </div>

                <div class="form-group">
                    <label for="type">Type d'expédition</label>
                    <select id="type" name="type">
                        <option value="colis">Colis</option>
                        <option value="palette">Palette</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="adr">Matières dangereuses (ADR)</label>
                    <select id="adr" name="adr">
                        <option value="non">Non</option>
                        <option value="oui">Oui</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="option_sup">Options de service</label>
                    <select id="option_sup" name="option_sup">
                        <option value="standard">Livraison standard</option>
                        <option value="premium_matin">Premium avant 13h</option>
                        <option value="rdv">Prise de RDV</option>
                        <option value="target">Date imposée</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="palettes">Nombre de palettes</label>
                    <input type="number" id="palettes" name="palettes" 
                           min="0" max="10" value="0" placeholder="0">
                </div>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="enlevement" name="enlevement" value="1">
                <label for="enlevement">Enlèvement à domicile</label>
            </div>

            <button type="button" class="btn-calculate" onclick="calculateRates()">
                🚚 Calculer les tarifs
            </button>
        </form>

        <div id="loading" class="loading">
            <div class="spinner"></div>
            <div>Calcul en cours...</div>
        </div>

        <div id="results" class="results-container">
            <h3>Résultats de calcul</h3>
            <div id="resultsContent"></div>
        </div>
    </div>

    <script>
    function calculateRates() {
        const form = document.getElementById('calc-form');
        const formData = new FormData(form);
        const loading = document.getElementById('loading');
        const results = document.getElementById('results');
        const resultsContent = document.getElementById('resultsContent');
        const button = document.querySelector('.btn-calculate');

        // Validation côté client
        const departement = formData.get('departement');
        const poids = parseFloat(formData.get('poids'));

        if (!departement || departement.length < 1) {
            alert('Veuillez saisir un département');
            return;
        }

        if (!poids || poids <= 0) {
            alert('Veuillez saisir un poids valide');
            return;
        }

        // Règle métier : poids > 60kg = forcément palette
        if (poids > 60 && formData.get('type') === 'colis') {
            formData.set('type', 'palette');
            console.log(`Poids ${poids}kg > 60kg : forcé en palette`);
        }

        // Affichage du loading
        button.disabled = true;
        loading.classList.add('show');
        results.classList.remove('show');

        // Préparation des données
        const params = new URLSearchParams();
        for (const [key, value] of formData.entries()) {
            params.append(key, value);
        }

        // Appel AJAX
        fetch('?ajax=calculate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params.toString()
        })
        .then(response => response.json())
        .then(data => {
            loading.classList.remove('show');
            button.disabled = false;
            
            if (data.success && data.carriers && data.carriers.length > 0) {
                displayResults(data);
            } else {
                displayError(data.message || 'Aucun résultat disponible', data.debug);
            }
        })
        .catch(error => {
            loading.classList.remove('show');
            button.disabled = false;
            displayError('Erreur de communication: ' + error.message);
        });
    }

    function displayResults(data) {
        const resultsContent = document.getElementById('resultsContent');
        const results = document.getElementById('results');
        
        // Tri des transporteurs par prix
        const carriers = data.carriers.sort((a, b) => a.price - b.price);
        
        let html = '<div class="results-grid">';
        
        carriers.forEach((carrier, index) => {
            const isBest = index === 0;
            html += `
                <div class="carrier-result ${isBest ? 'best' : ''}" data-carrier="${carrier.carrier_code}">
                    <div class="carrier-info">
                        <div class="carrier-name">${carrier.carrier_name}</div>
                        <div class="carrier-delay">⏰ ${carrier.delay}</div>
                    </div>
                    <div class="carrier-price">
                        <div class="price-value">${carrier.price_display}</div>
                        ${isBest ? '<div class="price-badge">MEILLEUR PRIX</div>' : ''}
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        // Informations complémentaires
        const economy = carriers.length > 1 ? 
            (carriers[carriers.length-1].price - carriers[0].price).toFixed(2) : '0.00';
        
        html += `
            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 0.85rem; color: var(--gray);">
                ⚡ Calcul: ${data.time_ms}ms • Économie max: ${economy}€
            </div>
        `;
        
        // Debug si activé
        if (data.debug) {
            html += `<div class="debug-info">${JSON.stringify(data.debug, null, 2)}</div>`;
        }
        
        resultsContent.innerHTML = html;
        results.classList.add('show');
    }
    
    function displayError(message, debug = null) {
        const resultsContent = document.getElementById('resultsContent');
        const results = document.getElementById('results');
        
        let html = `<div class="error-message">${message}</div>`;
        
        if (debug) {
            html += `<div class="debug-info">${JSON.stringify(debug, null, 2)}</div>`;
        }
        
        resultsContent.innerHTML = html;
        results.classList.add('show');
    }

    // Auto-format département
    document.getElementById('departement').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 2) value = value.slice(0, 2);
        e.target.value = value;
    });

    // Calcul automatique poids pallettes
    document.getElementById('type').addEventListener('change', function(e) {
        const palettesInput = document.getElementById('palettes');
        if (e.target.value === 'palette') {
            palettesInput.min = '1';
            if (palettesInput.value === '0') palettesInput.value = '1';
        } else {
            palettesInput.min = '0';
        }
    });
    </script>

    <div class="footer" style="background: #f8f9fa; border-top: 1px solid #e5e7eb; padding: 1.5rem; margin-top: 3rem;">
        <div class="footer-info" style="text-align: center; color: #6b7280; font-size: 0.875rem;">
            <span><?= APP_NAME ?> - Version <?= APP_VERSION ?></span> • 
            <span>Build <?= BUILD_NUMBER ?> (<?= BUILD_DATE ?>)</span> • 
            <span>&copy; <?= COPYRIGHT_YEAR ?> <?= APP_AUTHOR ?></span>
        </div>
    </div>
</body>
</html>

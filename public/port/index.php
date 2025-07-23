<?php
/**
 * Titre: Calculateur de frais de port - CORRECTION FLOW AVANCÉ
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
        ];
        
        // Validation stricte des paramètres
        if (empty($params['departement']) || !preg_match('/^[0-9]{2,3}$/', $params['departement'])) {
            throw new Exception('Département invalide');
        }
        if ($params['poids'] <= 0 || $params['poids'] > 32000) {
            throw new Exception('Poids invalide (1-32000 kg)');
        }
        if (!in_array($params['type'], ['colis', 'palette'])) {
            throw new Exception('Type d\'envoi invalide');
        }
        
        // Vérification affrètement pour poids > 3000kg
        if ($params['poids'] > 3000) {
            $response = [
                'success' => true,
                'affretement' => true,
                'carriers' => [
                    'affretement' => [
                        'prix_ht' => 0,
                        'prix_ttc' => 0,
                        'delai' => 'Sur devis',
                        'service' => 'Affrètement',
                        'message' => sprintf(
                            'Poids de %.1f kg - Affrètement requis. Contactez-nous pour un devis personnalisé.',
                            $params['poids']
                        )
                    ]
                ],
                'time_ms' => 0,
                'debug' => ['affretement_requis' => true, 'poids' => $params['poids']]
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
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
        
        // Vérification connexion DB
        if (!isset($db) || !($db instanceof PDO)) {
            throw new Exception('Connexion base de données indisponible');
        }
        
        // Initialisation Transport
        $transport = new Transport($db);
        
        // Calcul des tarifs
        $start_time = microtime(true);
        $results = $transport->calculateAll($params);
        $calc_time = round((microtime(true) - $start_time) * 1000, 2);
        
        // Formatage de la réponse
        $response = [
            'success' => true,
            'affretement' => false,
            'carriers' => [],
            'time_ms' => $calc_time,
            'debug' => [
                'params_received' => $params,
                'raw_results' => $results,
                'transport_class' => get_class($transport)
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
                        
                        // Délais selon transporteur et option
                        $delais = [
                            'xpo' => ['standard' => '24-48h', 'express' => '24h', 'urgent' => 'J+1'],
                            'heppner' => ['standard' => '24-72h', 'express' => '24-48h', 'urgent' => 'J+1'],
                            'kn' => ['standard' => '48-72h', 'express' => '24-48h', 'urgent' => 'J+1']
                        ];
                        
                        $delai = $delais[$carrier][$params['option_sup']] ?? '24-48h';
                        $service = ucfirst($params['option_sup']);
                        
                        $response['carriers'][$carrier] = [
                            'prix_ht' => $prix_ht,
                            'prix_ttc' => $prix_ttc,
                            'delai' => $delai,
                            'service' => $service,
                            'details' => [
                                'type' => $params['type'],
                                'adr' => $params['adr'] ? 'Oui' : 'Non',
                                'enlevement' => $params['enlevement'] ? 'Oui' : 'Non'
                            ]
                        ];
                    } elseif (is_array($result)) {
                        // Format complexe : tableau avec détails
                        $prix_ht = round(floatval($result['prix_ht'] ?? $result['prix'] ?? 0), 2);
                        $prix_ttc = round(floatval($result['prix_ttc'] ?? $prix_ht * 1.2), 2);
                        
                        $response['carriers'][$carrier] = [
                            'prix_ht' => $prix_ht,
                            'prix_ttc' => $prix_ttc,
                            'delai' => $result['delai'] ?? '24-48h',
                            'service' => $result['service'] ?? ucfirst($params['option_sup']),
                            'details' => $result['details'] ?? []
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
                'message' => 'Aucun transporteur disponible pour ces critères. Vérifiez les paramètres.'
            ];
        }
        
        // Ajout méta-informations
        $response['debug']['carriers_found'] = count($valid_results);
        $response['debug']['total_tested'] = count($results['results'] ?? []);
        
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
include ROOT_PATH . '/templates/header.php';
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
    transition: all 0.3s ease-in-out;
}

.calc-step-content.active {
    display: block;
    opacity: 1;
    transform: translateY(0);
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
    transform: translateY(-1px);
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
    transform: translateY(-1px);
}

.calc-toggle-btn.active {
    border-color: var(--port-primary);
    background: var(--port-primary);
    color: white;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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

.calc-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
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
    position: relative;
    overflow: hidden;
}

.calc-result-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--port-primary);
    transform: scaleY(0);
    transition: all 0.3s;
}

.calc-result-card:hover {
    border-color: var(--port-primary);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.1);
}

.calc-result-card:hover::before {
    transform: scaleY(1);
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
    color: var(--port-success);
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

.calc-result-details {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--port-border);
    font-size: 0.9rem;
}

.calc-result-details div {
    display: flex;
    justify-content: space-between;
    margin: 0.25rem 0;
}

/* Indicateurs de progression */
.calc-progress-indicator {
    text-align: center;
    padding: 1rem;
    background: rgba(37, 99, 235, 0.05);
    border-radius: 0.5rem;
    margin: 1rem 0;
    color: var(--port-primary);
    font-weight: 500;
}

/* Messages temporaires */
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

/* Debug panel amélioré */
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

.debug-toggle {
    transition: transform 0.2s;
}

.debug-panel.expanded .debug-toggle {
    transform: rotate(180deg);
}

.debug-content {
    padding: 10px;
    max-height: 250px;
    overflow-y: auto;
    display: none;
}

.debug-panel.expanded .debug-content {
    display: block;
}

.debug-entry {
    margin-bottom: 8px;
    padding: 4px 8px;
    background: #f8f9fa;
    border-radius: 3px;
    border-left: 3px solid var(--port-primary);
    font-family: monospace;
}

.debug-entry pre {
    margin: 4px 0;
    font-size: 10px;
    white-space: pre-wrap;
    color: #666;
}

/* Responsive */
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
    
    .calc-temp-message {
        right: 10px;
        left: 10px;
        max-width: none;
    }
}

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
    
    .calc-results-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}

/* Animations */
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.calc-results-wrapper {
    animation: slideIn 0.3s ease-out;
}

.calc-form.loading {
    opacity: 0.7;
    pointer-events: none;
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
                                       placeholder="150" min="1" max="32000" step="0.1" required>
                                <span style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--port-text); font-weight: 600;">kg</span>
                            </div>
                            <small class="calc-help">Type suggéré automatiquement selon le poids. > 3000kg = affrètement</small>
                        </div>

                        <div class="calc-form-group">
                            <label for="type" class="calc-label">📦 Type d'envoi *</label>
                            <select id="type" name="type" class="calc-select" required>
                                <option value="">-- Sélection automatique --</option>
                                <option value="colis">📦 Colis (≤ 150kg)</option>
                                <option value="palette">🏗️ Palette (> 150kg)</option>
                            </select>
                        </div>

                        <!-- Options palettes - VISIBLE UNIQUEMENT SI PALETTE -->
                        <div class="calc-form-group" id="palettesGroup" style="display: none;">
                            <label for="palettes" class="calc-label">🏗️ Nombre de palettes *</label>
                            <select id="palettes" name="palettes" class="calc-select">
                                <option value="1">1 palette</option>
                                <option value="2">2 palettes</option>
                                <option value="3">3 palettes</option>
                                <option value="4">4 palettes</option>
                            </select>
                            <small class="calc-help">Nombre total de palettes dans l'envoi</small>
                        </div>

                        <div class="calc-form-group" id="paletteEurGroup" style="display: none;">
                            <label for="palette_eur" class="calc-label">🔄 Palettes EUR consignées</label>
                            <select id="palette_eur" name="palette_eur" class="calc-select">
                                <option value="0">Aucune (0)</option>
                                <option value="1">1 palette EUR</option>
                                <option value="2">2 palettes EUR</option>
                                <option value="3">3 palettes EUR</option>
                                <option value="4">4 palettes EUR</option>
                            </select>
                            <small class="calc-help">Palettes Europe à récupérer chez le destinataire (consigne)</small>
                        </div>
                    </div>

                    <!-- Étape 3: Options et Services -->
                    <div class="calc-step-content" data-step="3">
                        <!-- ADR (Matières dangereuses) -->
                        <div class="calc-form-group">
                            <label class="calc-label">⚠️ Matières dangereuses (ADR) *</label>
                            <div class="calc-toggle-group">
                                <button type="button" class="calc-toggle-btn" data-adr="non">✅ Non - Transport standard</button>
                                <button type="button" class="calc-toggle-btn" data-adr="oui">⚠️ Oui - Matières dangereuses</button>
                            </div>
                            <input type="hidden" id="adr" name="adr" value="">
                            <small class="calc-help">Les matières dangereuses nécessitent un transport spécialisé ADR (+62€ minimum)</small>
                        </div>

                        <!-- Type de service -->
                        <div class="calc-form-group">
                            <label class="calc-label">🚚 Type de service</label>
                            <div class="calc-toggle-group">
                                <button type="button" class="calc-toggle-btn active" data-enlevement="non">📮 Livraison standard</button>
                                <button type="button" class="calc-toggle-btn" data-enlevement="oui">🚚 Enlèvement + livraison</button>
                            </div>
                            <input type="hidden" id="enlevement" name="enlevement" value="non">
                            <small class="calc-help">Enlèvement = prise en charge à votre adresse</small>
                        </div>

                        <!-- Options supplémentaires -->
                        <div class="calc-form-group">
                            <label for="option_sup" class="calc-label">⚙️ Options de livraison</label>
                            <select id="option_sup" name="option_sup" class="calc-select">
                                <option value="standard">📦 Standard (économique)</option>
                                <option value="express">⚡ Express (+1 jour plus rapide)</option>
                                <option value="urgent">🚨 Urgent (livraison J+1)</option>
                            </select>
                            <small class="calc-help">Les options express et urgent sont disponibles selon le transporteur</small>
                        </div>

                        <!-- Bouton de calcul -->
                        <div class="calc-form-group">
                            <button type="submit" id="calculateBtn" class="calc-btn-primary" disabled>
                                🧮 Calculer les tarifs
                            </button>
                            <small class="calc-help">Le calcul se lance automatiquement une fois tous les paramètres saisis</small>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- RÉSULTATS -->
        <section class="calc-results-panel">
            <div class="calc-results-header">
                <h2>📊 Résultats de calcul</h2>
                <div id="calcStatus" class="calc-status">⏳ En attente de vos paramètres...</div>
            </div>
            
            <div id="resultsContent" class="calc-results-content">
                <div class="calc-welcome">
                    <div class="calc-welcome-icon">🚛</div>
                    <h3>Calculateur Intelligent</h3>
                    <p>Flow automatique avec validation complète avant calcul</p>
                    <div style="margin-top: 2rem; padding: 1rem; background: rgba(37, 99, 235, 0.05); border-radius: 0.5rem;">
                        <strong>Étapes :</strong><br>
                        1️⃣ Saisissez le département<br>
                        2️⃣ Indiquez le poids (type auto-sélectionné)<br>
                        3️⃣ Confirmez les options palettes si nécessaire<br>
                        4️⃣ Choisissez ADR Oui/Non<br>
                        ⚡ Calcul automatique !
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- Panel de debug amélioré -->
<div class="debug-panel" id="debugPanel">
    <div class="debug-header" onclick="this.parentElement.classList.toggle('expanded')">
        <span>🔧 Debug</span>
        <span class="debug-toggle">▼</span>
    </div>
    <div class="debug-content" id="debugContent">
        <div class="debug-entry">Prêt pour le debug...</div>
    </div>
</div>

<!-- JavaScript pour le flow intelligent CORRIGÉ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🧮 Calculateur initialisé - Version flow corrigée');
    
    // Cache DOM
    const dom = {
        form: document.getElementById('calculatorForm'),
        departement: document.getElementById('departement'),
        poids: document.getElementById('poids'),
        type: document.getElementById('type'),
        palettes: document.getElementById('palettes'),
        paletteEur: document.getElementById('palette_eur'),
        adr: document.getElementById('adr'),
        enlevement: document.getElementById('enlevement'),
        optionSup: document.getElementById('option_sup'),
        calculateBtn: document.getElementById('calculateBtn'),
        resultsContent: document.getElementById('resultsContent'),
        calcStatus: document.getElementById('calcStatus'),
        stepBtns: document.querySelectorAll('.calc-step-btn'),
        stepContents: document.querySelectorAll('.calc-step-content'),
        debugContent: document.getElementById('debugContent'),
        palettesGroup: document.getElementById('palettesGroup'),
        paletteEurGroup: document.getElementById('paletteEurGroup')
    };
    
    // État du calculateur
    let state = {
        currentStep: 1,
        userInteracting: false,
        lastProgressTime: 0,
        adrSelected: false,
        palettesConfigured: false,
        isCalculating: false
    };
    
    // Debug helper amélioré
    function addDebug(message, data = null) {
        const timestamp = new Date().toLocaleTimeString();
        const debugEntry = document.createElement('div');
        debugEntry.className = 'debug-entry';
        
        let content = `<strong>${timestamp}:</strong> ${message}`;
        if (data) {
            content += `<pre>${JSON.stringify(data, null, 2)}</pre>`;
        }
        
        debugEntry.innerHTML = content;
        dom.debugContent.appendChild(debugEntry);
        dom.debugContent.scrollTop = dom.debugContent.scrollHeight;
        
        // Limiter à 20 entrées
        const entries = dom.debugContent.querySelectorAll('.debug-entry');
        if (entries.length > 20) {
            entries[0].remove();
        }
    }
    
    // Message temporaire
    function showTempMessage(message, type = 'info', duration = 3000) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `calc-temp-message ${type}`;
        msgDiv.textContent = message;
        document.body.appendChild(msgDiv);
        
        setTimeout(() => msgDiv.classList.add('show'), 100);
        setTimeout(() => {
            msgDiv.classList.remove('show');
            setTimeout(() => msgDiv.remove(), 300);
        }, duration);
    }
    
    addDebug('Module initialisé');
    
    // Validation département
    function validateDepartement() {
        if (!dom.departement) return false;
        const value = dom.departement.value.trim();
        const isValid = /^(0[1-9]|[1-8][0-9]|9[0-5]|2[AB])$/i.test(value);
        
        if (isValid) {
            dom.departement.style.borderColor = 'var(--port-success)';
        } else {
            dom.departement.style.borderColor = '';
        }
        
        return isValid;
    }
    
    // Validation poids
    function validatePoids() {
        if (!dom.poids) return false;
        const value = parseFloat(dom.poids.value);
        const isValid = value >= 1 && value <= 32000 && !isNaN(value);
        
        if (isValid) {
            dom.poids.style.borderColor = 'var(--port-success)';
        } else {
            dom.poids.style.borderColor = '';
        }
        
        return isValid;
    }
    
    // Validation formulaire complète
    function validateForm() {
        const deptValid = validateDepartement();
        const poidsValid = validatePoids();
        const typeValid = dom.type.value !== '';
        const adrValid = state.adrSelected;
        
        // Si palette, vérifier que palettes/EUR sont configurées
        let palettesValid = true;
        if (dom.type.value === 'palette') {
            palettesValid = state.palettesConfigured;
        }
        
        const allValid = deptValid && poidsValid && typeValid && adrValid && palettesValid;
        
        // Mise à jour bouton calcul
        if (dom.calculateBtn) {
            dom.calculateBtn.disabled = !allValid;
            if (allValid) {
                dom.calculateBtn.style.opacity = '1';
                dom.calculateBtn.style.cursor = 'pointer';
            } else {
                dom.calculateBtn.style.opacity = '0.6';
                dom.calculateBtn.style.cursor = 'not-allowed';
            }
        }
        
        return allValid;
    }
    
    // Gestion des étapes
    function activateStep(step) {
        const now = Date.now();
        if (now - state.lastProgressTime < 300) return; // Anti-spam
        
        state.lastProgressTime = now;
        state.currentStep = step;
        
        addDebug(`Activation étape ${step}`);
        
        // Mise à jour visuelle des étapes
        dom.stepBtns.forEach(btn => {
            const btnStep = parseInt(btn.dataset.step);
            btn.classList.toggle('active', btnStep === step);
            btn.classList.toggle('completed', btnStep < step);
        });
        
        // Affichage du contenu
        dom.stepContents.forEach(content => {
            const contentStep = parseInt(content.dataset.step);
            if (contentStep === step) {
                content.style.display = 'block';
                setTimeout(() => {
                    content.classList.add('active');
                }, 50);
            } else {
                content.classList.remove('active');
                setTimeout(() => {
                    content.style.display = 'none';
                }, 200);
            }
        });
        
        // Focus intelligent
        setTimeout(() => {
            if (step === 1 && dom.departement) dom.departement.focus();
            if (step === 2 && dom.poids) dom.poids.focus();
            if (step === 3 && dom.type) dom.type.focus();
        }, 300);
        
        validateForm();
    }
    
    // Auto-sélection type par poids CORRIGÉE
    function autoSelectType() {
        if (!dom.poids || !dom.type) return;
        
        const poids = parseFloat(dom.poids.value);
        if (isNaN(poids) || poids <= 0) return;
        
        // Règles de sélection
        let suggestedType = '';
        let reason = '';
        
        if (poids > 3000) {
            suggestedType = 'palette';
            reason = `${poids}kg → PALETTE (Affrètement > 3000kg)`;
            showTempMessage('⚠️ Poids > 3000kg - Affrètement requis', 'warning', 4000);
        } else if (poids <= 150) {
            suggestedType = 'colis';
            reason = `${poids}kg → COLIS (≤ 150kg)`;
        } else {
            suggestedType = 'palette';
            reason = `${poids}kg → PALETTE (> 150kg)`;
        }
        
        // Application
        if (dom.type.value === '' || dom.type.value !== suggestedType) {
            dom.type.value = suggestedType;
            handleTypeChange();
            addDebug('Auto-sélection type', { poids, type: suggestedType, reason });
            showTempMessage(reason, 'success', 2500);
        }
    }
    
    // Gestion type palette/colis CORRIGÉE
    function handleTypeChange() {
        const type = dom.type.value;
        
        if (type === 'palette') {
            dom.palettesGroup.style.display = 'block';
            dom.paletteEurGroup.style.display = 'block';
            state.palettesConfigured = false; // Reset
            
            addDebug('Mode palette activé - Configuration requise');
            showTempMessage('🏗️ Mode palette - Configurez le nombre de palettes', 'info', 3000);
        } else {
            dom.palettesGroup.style.display = 'none';
            dom.paletteEurGroup.style.display = 'none';
            state.palettesConfigured = true; // Pas de config nécessaire pour colis
            
            addDebug('Mode colis activé');
        }
        
        validateForm();
    }
    
    // Configuration palettes
    function handlePalettesConfig() {
        if (dom.type.value === 'palette') {
            const nbPalettes = parseInt(dom.palettes.value) || 1;
            const nbEur = parseInt(dom.paletteEur.value) || 0;
            
            state.palettesConfigured = true;
            addDebug('Configuration palettes', { palettes: nbPalettes, eur: nbEur });
            showTempMessage(`✅ ${nbPalettes} palette(s) + ${nbEur} EUR configurées`, 'success', 2000);
            validateForm();
        }
    }
    
    // Auto-progression INTELLIGENTE avec PAUSES
    function smartProgress() {
        if (state.userInteracting || state.isCalculating) return;
        
        const deptValid = validateDepartement();
        const poidsValid = validatePoids();
        const typeSelected = dom.type.value !== '';
        
        // Étape 1 → 2 : Département valide
        if (deptValid && state.currentStep === 1) {
            addDebug('Auto-progression: Étape 1 → 2');
            showTempMessage('📍 Département validé → Saisie du poids', 'success', 2000);
            setTimeout(() => activateStep(2), 800);
        }
        // Étape 2 → 3 : Département + Poids + Type valides
        else if (deptValid && poidsValid && typeSelected && state.currentStep === 2) {
            addDebug('Auto-progression: Étape 2 → 3');
            showTempMessage('📦 Informations envoi complètes → Options finales', 'success', 2000);
            setTimeout(() => activateStep(3), 800);
        }
        // PAS de calcul automatique - Attendre ADR explicitement
    }
    
    // Events département
    if (dom.departement) {
        dom.departement.addEventListener('focus', () => { 
            state.userInteracting = true; 
        });
        
        dom.departement.addEventListener('blur', () => { 
            state.userInteracting = false; 
            setTimeout(() => {
                if (validateDepartement()) {
                    smartProgress();
                }
            }, 200);
        });
        
        dom.departement.addEventListener('input', () => {
            if (validateDepartement()) {
                setTimeout(() => {
                    if (!state.userInteracting) {
                        smartProgress();
                    }
                }, 500);
            }
        });
    }
    
    // Events poids
    if (dom.poids) {
        dom.poids.addEventListener('focus', () => { 
            state.userInteracting = true; 
        });
        
        dom.poids.addEventListener('blur', () => { 
            state.userInteracting = false; 
            setTimeout(() => {
                if (validatePoids()) {
                    autoSelectType();
                    smartProgress();
                }
            }, 200);
        });
        
        dom.poids.addEventListener('input', () => {
            if (validatePoids()) {
                autoSelectType();
                setTimeout(() => {
                    if (!state.userInteracting) {
                        smartProgress();
                    }
                }, 500);
            }
        });
    }
    
    // Events type
    if (dom.type) {
        dom.type.addEventListener('change', () => {
            handleTypeChange();
            setTimeout(smartProgress, 300);
        });
    }
    
    // Events palettes
    if (dom.palettes) {
        dom.palettes.addEventListener('change', handlePalettesConfig);
    }
    if (dom.paletteEur) {
        dom.paletteEur.addEventListener('change', handlePalettesConfig);
    }
    
    // Gestion toggles ADR - PAUSE AVANT CALCUL
    document.querySelectorAll('[data-adr]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-adr]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            dom.adr.value = this.dataset.adr;
            state.adrSelected = true;
            
            const isAdr = this.dataset.adr === 'oui';
            addDebug(`ADR sélectionné: ${isAdr ? 'OUI' : 'NON'}`);
            
            // Animation
            this.style.animation = 'pulse 0.5s ease-in-out';
            setTimeout(() => { this.style.animation = ''; }, 500);
            
            if (isAdr) {
                showTempMessage('⚠️ ADR activé - Majoration appliquée (+62€ min)', 'warning', 3000);
            } else {
                showTempMessage('✅ Transport standard sélectionné', 'success', 2000);
            }
            
            validateForm();
            
            // CALCUL AUTOMATIQUE avec délai SEULEMENT si formulaire complet
            if (validateForm()) {
                addDebug('Formulaire complet - Calcul automatique dans 2s');
                showTempMessage('🧮 Calcul automatique dans 2 secondes...', 'info', 2000);
                setTimeout(() => {
                    handleCalculate();
                }, 2000);
            }
        });
    });
    
    // Gestion toggles enlèvement
    document.querySelectorAll('[data-enlevement]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-enlevement]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            dom.enlevement.value = this.dataset.enlevement;
            
            const isEnlevement = this.dataset.enlevement === 'oui';
            addDebug(`Enlèvement: ${isEnlevement ? 'OUI' : 'NON'}`);
            
            if (isEnlevement) {
                showTempMessage('🚚 Enlèvement activé - Prise en charge à domicile', 'info', 2000);
            } else {
                showTempMessage('📮 Livraison standard sélectionnée', 'success', 1500);
            }
        });
    });
    
    // Navigation manuelle entre étapes
    dom.stepBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const step = parseInt(btn.dataset.step);
            activateStep(step);
        });
    });
    
    // Calcul principal AMÉLIORÉ
    async function handleCalculate() {
        if (state.isCalculating) {
            addDebug('Calcul déjà en cours - Ignoré');
            return;
        }
        
        if (!validateForm()) {
            addDebug('Validation échouée - Calcul annulé');
            showTempMessage('❌ Veuillez compléter tous les champs requis', 'warning', 3000);
            return;
        }
        
        state.isCalculating = true;
        dom.form.classList.add('loading');
        dom.calcStatus.textContent = '⏳ Calcul en cours...';
        addDebug('Début calcul');
        
        const formData = new FormData(dom.form);
        const params = Object.fromEntries(formData.entries());
        
        addDebug('Paramètres envoyés', params);
        
        try {
            const response = await fetch('?ajax=calculate', {
                method: 'POST',
                body: new URLSearchParams(params),
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                addDebug('Réponse non-JSON reçue', { contentType, textPreview: text.substring(0, 200) });
                throw new Error('Réponse serveur invalide (HTML au lieu de JSON)');
            }
            
            const data = await response.json();
            addDebug('Réponse JSON reçue', data);
            
            if (data.success) {
                displayResults(data);
                dom.calcStatus.textContent = '✅ Calcul terminé';
                showTempMessage('✅ Calcul terminé avec succès', 'success', 2000);
            } else {
                throw new Error(data.error || 'Erreur inconnue');
            }
        } catch (error) {
            console.error('Erreur calcul:', error);
            addDebug('ERREUR', { message: error.message, stack: error.stack });
            dom.calcStatus.textContent = '❌ Erreur: ' + error.message;
            showTempMessage('❌ Erreur: ' + error.message, 'warning', 5000);
        } finally {
            state.isCalculating = false;
            dom.form.classList.remove('loading');
        }
    }
    
    // Affichage résultats AMÉLIORÉ
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
                const carrierNames = {
                    'xpo': 'XPO Logistics',
                    'heppner': 'Heppner',
                    'kn': 'Kuehne + Nagel',
                    'affretement': 'Affrètement',
                    'info': 'Information'
                };
                
                const name = carrierNames[carrier] || carrier.toUpperCase();
                const prixTTC = result.prix_ttc || 0;
                const prixHT = result.prix_ht || 0;
                const delai = result.delai || 'N/A';
                const service = result.service || 'Standard';
                
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
                    html += '<div style="text-align: center; color: var(--port-text); font-style: italic; padding: 1rem;">';
                    html += result.message || 'Contactez-nous pour un devis';
                    html += '</div>';
                }
                
                // Détails du service
                if (result.details || service !== 'Standard') {
                    html += '<div class="calc-result-details">';
                    html += '<div><span>Service:</span><span>' + service + '</span></div>';
                    if (result.details) {
                        Object.entries(result.details).forEach(([key, value]) => {
                            html += '<div><span>' + key + ':</span><span>' + value + '</span></div>';
                        });
                    }
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
        addDebug('Résultats affichés', { carriers: Object.keys(data.carriers || {}) });
    }
    
    // Soumission formulaire
    if (dom.form) {
        dom.form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleCalculate();
        });
    }
    
    // Bouton calcul manuel
    if (dom.calculateBtn) {
        dom.calculateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handleCalculate();
        });
    }
    
    // Gestion clavier améliorée
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey && e.target.matches('input, select')) {
            e.preventDefault();
            
            const deptValid = validateDepartement();
            const poidsValid = validatePoids();
            
            if (state.currentStep === 1 && deptValid) {
                activateStep(2);
            } else if (state.currentStep === 2 && deptValid && poidsValid) {
                activateStep(3);
            } else if (state.currentStep >= 3 && validateForm()) {
                handleCalculate();
            }
        }
    });
    
    // Initialisation finale
    validateForm();
    addDebug('Tous les événements configurés');
});
</script>

<?php
// ========================================
// 🎨 CHARGEMENT FOOTER
// ========================================
include ROOT_PATH . '/templates/footer.php';
?>

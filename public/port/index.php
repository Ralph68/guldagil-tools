<?php
/**
 * Titre: Calculateur de frais de port - Interface complète
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configuration
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

// Variables pour header/footer
$version_info = getVersionInfo();
$page_title = 'Calculateur de Frais de Port';
$page_subtitle = 'Comparaison multi-transporteurs XPO, Heppner, Kuehne+Nagel';
$page_description = 'Calculateur de frais de port professionnel - Comparaison instantanée des tarifs de transport';
$current_module = 'calculateur';
$user_authenticated = true;
$module_css = true;
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '🚛', 'text' => 'Calculateur', 'url' => '/port/', 'active' => true]
];

session_start();

// Authentification
if (!$user_authenticated) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// GESTION AJAX CALCULATE
if (isset($_GET['ajax']) && $_GET['ajax'] === 'calculate') {
    header('Content-Type: application/json');
    
    try {
        parse_str(file_get_contents('php://input'), $post_data);
        
        $params = [
            'departement' => str_pad(trim($post_data['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
            'poids' => floatval($post_data['poids'] ?? 0),
            'type' => strtolower(trim($post_data['type'] ?? 'colis')),
            'adr' => (($post_data['adr'] ?? 'non') === 'oui'),
            'option_sup' => trim($post_data['option_sup'] ?? 'standard'),
            'enlevement' => ($post_data['enlevement'] ?? 'non') === 'oui',
            'palettes' => max(1, intval($post_data['palettes'] ?? 1)),
            'palette_eur' => intval($post_data['palette_eur'] ?? 0),
        ];
        
        if (empty($params['departement']) || !preg_match('/^[0-9]{2,3}$/', $params['departement'])) {
            throw new Exception('Département invalide');
        }
        if ($params['poids'] <= 0 || $params['poids'] > 32000) {
            throw new Exception('Poids invalide');
        }
        
        $transport_file = __DIR__ . '/calculs/transport.php';
        if (!file_exists($transport_file)) {
            throw new Exception('Transport non trouvé: ' . $transport_file);
        }
        
        require_once $transport_file;
        $transport = new Transport($db);
        
        $start_time = microtime(true);
        $results = $transport->calculateAll($params);
        $calc_time = round((microtime(true) - $start_time) * 1000, 2);
        
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
        
        $carrier_results = $results['results'] ?? $results;
        
        foreach ($carrier_results as $carrier => $price) {
            // Masquer temporairement K+N qui ne sera pas utilisé
    if ($carrier === 'kuehne_nagel' || $carrier === 'kn') {
        continue; // Module désactivé - non utilisé
    }
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

// Chargement header
require_once ROOT_PATH . '/templates/header.php';
?>

<link rel="stylesheet" href="./assets/css/calculateur.css?v=<?= $build_number ?>">

<div class="calc-container">
    <header class="calc-header">
        <div class="calc-header-content">
            <h1 class="calc-title">🚛 Calculateur de Frais de Port</h1>
            <p class="calc-subtitle">Comparez instantanément les tarifs XPO, Heppner et Kuehne+Nagel</p>
        </div>
    </header>
    
    <main class="calc-main">
        <!-- FORMULAIRE -->
        <section class="calc-form-panel">
            <nav class="calc-steps">
                <button type="button" class="calc-step-btn active" data-step="1">
                    <span class="calc-step-indicator">1</span>
                    <span class="calc-step-label">📍 Destination</span>
                </button>
                <button type="button" class="calc-step-btn disabled" data-step="2">
                    <span class="calc-step-indicator">2</span>
                    <span class="calc-step-label">📦 Expédition</span>
                </button>
                <button type="button" class="calc-step-btn disabled" data-step="3">
                    <span class="calc-step-indicator">3</span>
                    <span class="calc-step-label">🚀 Options</span>
                </button>
            </nav>
            
            <form id="calculatorForm" class="calc-form">
                <!-- Étape 1: Destination -->
                <div class="calc-form-step active" data-step="1">
                    <div class="calc-form-group">
                        <label class="calc-label" for="departement">Département de destination *</label>
                        <input type="text" id="departement" name="departement" class="calc-input" 
                               placeholder="Ex: 67, 75, 13..." maxlength="3" required
                               autocomplete="off">
                        <small class="calc-help">Code département français (2-3 chiffres)</small>
                    </div>
                </div>
                
                <!-- Étape 2: Expédition -->
                <div class="calc-form-step" data-step="2">
                    <div class="calc-form-group">
                        <label class="calc-label" for="poids">Poids total (kg) *</label>
                        <input type="number" id="poids" name="poids" class="calc-input" 
                               min="0.1" max="32000" step="0.1" placeholder="Ex: 25.5" required>
                        <small class="calc-help">Entre 0.1 et 32000 kg</small>
                    </div>
                    
                    <div class="calc-form-group">
                        <label class="calc-label" for="type">Type d'expédition *</label>
                        <select id="type" name="type" class="calc-input" required>
                            <option value="">Choisir...</option>
                            <option value="colis">📦 Colis</option>
                            <option value="palette">🏗️ Palette(s) EUR</option>
                        </select>
                    </div>
                    
                    <div class="calc-form-group calc-group-palettes" id="palettesGroup" style="display: none;">
                        <label class="calc-label" for="palettes">Nombre de palettes EUR</label>
                        <input type="number" id="palettes" name="palettes" class="calc-input" 
                               min="1" max="20" value="1">
                    </div>
                    
                    <div class="calc-form-group calc-group-palette-eur" id="paletteEurGroup" style="display: none;">
                        <label class="calc-label" for="palette_eur">
                            🏷️ Palettes EUR consignées
                            <span class="calc-label-optional">- Facultatif</span>
                        </label>
                        <input type="number" id="palette_eur" name="palette_eur" class="calc-input" 
                               min="0" value="0" step="1" placeholder="Nombre de palettes EUR">
                        <small class="calc-help calc-help-palette">
                            💡 <strong>0 = palette perdue</strong> (économise 1,80€ de consigne XPO par palette)
                        </small>
                    </div>
                    
                    <div class="calc-form-group">
                        <label class="calc-label">Transport ADR (matières dangereuses) *</label>
                        <div class="calc-toggle-group">
                            <button type="button" class="calc-toggle-btn active" data-adr="non">❌ Non</button>
                            <button type="button" class="calc-toggle-btn" data-adr="oui">⚠️ Oui</button>
                        </div>
                        <input type="hidden" id="adr" name="adr" value="non">
                    </div>
                </div>
                
                <!-- Étape 3: Options -->
                <div class="calc-form-step" data-step="3">
                    <div class="calc-form-group">
                        <label class="calc-label">Service de livraison</label>
                        <div class="calc-options-grid">
                            <label class="calc-option-card">
                                <input type="radio" name="option_sup" value="standard" checked>
                                <div class="calc-option-content">
                                    <div class="calc-option-title">Standard</div>
                                    <div class="calc-option-desc">Selon grille délais</div>
                                    <div class="calc-option-price">Inclus</div>
                                </div>
                            </label>
                            
                            <label class="calc-option-card">
                                <input type="radio" name="option_sup" value="rdv">
                                <div class="calc-option-content">
                                    <div class="calc-option-title">Sur RDV</div>
                                    <div class="calc-option-desc">Prise de rendez-vous</div>
                                    <div class="calc-option-price">~12€</div>
                                </div>
                            </label>
                            
                            <label class="calc-option-card">
                                <input type="radio" name="option_sup" value="premium_matin">
                                <div class="calc-option-content">
                                    <div class="calc-option-title">Premium</div>
                                    <div class="calc-option-desc">Garantie matin</div>
                                    <div class="calc-option-price">~15€</div>
                                </div>
                            </label>
                            
                            <label class="calc-option-card">
                                <input type="radio" name="option_sup" value="target">
                                <div class="calc-option-content">
                                    <div class="calc-option-title">Date imposée</div>
                                    <div class="calc-option-desc">Date précise</div>
                                    <div class="calc-option-price">~25€</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="calc-form-group">
                        <label class="calc-label">Enlèvement à votre adresse</label>
                        <div class="calc-toggle-group">
                            <button type="button" class="calc-toggle-btn active" data-enlevement="non">❌ Non</button>
                            <button type="button" class="calc-toggle-btn" data-enlevement="oui">📤 Oui</button>
                        </div>
                        <input type="hidden" id="enlevement" name="enlevement" value="non">
                        <small class="calc-help">Gratuit chez Heppner, ~25€ chez XPO</small>
                    </div>
                    
                    <div class="calc-form-actions">
                        <button type="submit" class="calc-btn-primary">🧮 Calculer les tarifs</button>
                        <button type="button" class="calc-btn-secondary" onclick="resetForm()">🔄 Nouvelle recherche</button>
                    </div>
                </div>
            </form>
        </section>
        
        <!-- RÉSULTATS -->
        <section class="calc-results-panel">
            <div class="calc-results-header">
                <h2 class="calc-results-title">💰 Tarifs</h2>
                <div class="calc-status" id="calcStatus">⏳ En attente...</div>
            </div>
            
            <div class="calc-results-content" id="resultsContent">
                <div class="calc-empty-state">
                    <div class="calc-empty-icon">🧮</div>
                    <p class="calc-empty-text">Complétez le formulaire pour voir les tarifs</p>
                </div>
            </div>
            
            <!-- Historique -->
            <div class="calc-section calc-history" id="historySection" style="display: none;">
                <div class="calc-section-header" onclick="toggleHistory()">
                    <span>📋 Historique des calculs</span>
                    <span class="calc-toggle-icon" id="historyToggle">▼</span>
                </div>
                <div class="calc-section-content" id="historyContent">
                    <p class="calc-section-empty">Aucun calcul dans l'historique</p>
                </div>
            </div>
            
            <!-- Debug -->
            <div class="calc-section calc-debug" id="debugContainer" style="display: none;">
                <div class="calc-section-header" onclick="toggleDebug()">
                    <span>🐛 Debug Transport</span>
                    <span class="calc-toggle-icon" id="debugToggle">▼</span>
                </div>
                <div class="calc-section-content" id="debugContent"></div>
            </div>
        </section>
    </main>
</div>

<script src="assets/js/calculateur.js?v=<?= $version_info['build'] ?>"></script>

<?php
require_once ROOT_PATH . '/templates/footer.php';
?>

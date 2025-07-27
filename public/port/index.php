<?php
/**
 * Titre: Calculateur de frais de port - CORRECTION FLOW AVANCÉ
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 * Origine : index.php250723.bak
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
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/database.php';

// Variables pour header/footer
$page_title = 'Calculateur de Frais de Port';
$page_subtitle = 'Comparaison multi-transporteurs XPO, Heppner, Kuehne+Nagel';
$page_description = 'Calculateur de frais de port professionnel - Comparaison instantanée des tarifs de transport';
$current_module = 'port';
$module_css = true;
$module_js = true;

$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '🚛', 'text' => 'Calculateur', 'url' => '/port/', 'active' => true]
];

// Correction session doublée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// 🔧 GESTION AJAX CALCULATE
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
include_once ROOT_PATH . '/templates/header.php';
?>

<div class="calc-container">
    <main class="calc-main">
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
                        
                        <div class="form-nav-buttons">
                            <div></div><!-- Espace vide pour l'alignement -->
                            <button type="button" class="btn-next" data-goto="2">Suivant</button>
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
                        
                        <div class="form-nav-buttons">
                            <button type="button" class="btn-prev" data-goto="1">Précédent</button>
                            <button type="button" class="btn-next" data-goto="3">Suivant</button>
                        </div>
                    </div>

                    <!-- Étape 3: Options et Services -->
                    <div class="calc-step-content" data-step="3">
                        <!-- ADR (Matières dangereuses) -->
                        <div class="calc-form-group">
                            <label class="calc-label">⚠️ Matières dangereuses (ADR) *</label>
                            <div class="delivery-options">
                                <div class="delivery-option">
                                    <input type="radio" id="adr-non" name="adr" value="non" checked>
                                    <label for="adr-non">✅ Non - Transport standard</label>
                                </div>
                                <div class="delivery-option">
                                    <input type="radio" id="adr-oui" name="adr" value="oui">
                                    <label for="adr-oui">⚠️ Oui - Matières dangereuses</label>
                                </div>
                            </div>
                            <small class="calc-help">Les matières dangereuses nécessitent un transport spécialisé ADR (+62€ minimum)</small>
                        </div>

                        <!-- Options de livraison exclusives -->
                        <div class="calc-form-group">
                            <label class="calc-label">🚚 Options de livraison</label>
                            <div class="delivery-options">
                                <div class="delivery-option">
                                    <input type="radio" id="option-standard" name="option_sup" value="standard" checked>
                                    <label for="option-standard">📦 Standard</label>
                                </div>
                                <div class="delivery-option">
                                    <input type="radio" id="option-premium" name="option_sup" value="premium">
                                    <label for="option-premium">⭐ Premium</label>
                                </div>
                                <div class="delivery-option">
                                    <input type="radio" id="option-rdv" name="option_sup" value="rdv">
                                    <label for="option-rdv">🕒 Rendez-vous</label>
                                </div>
                                <div class="delivery-option">
                                    <input type="radio" id="option-date" name="option_sup" value="date">
                                    <label for="option-date">📅 Date fixe</label>
                                </div>
                            </div>
                            <small class="calc-help">Choisissez l'option de livraison qui correspond à vos besoins</small>
                        </div>

                        <!-- Case à cocher pour l'enlèvement -->
                        <div class="checkbox-container">
                            <input type="checkbox" id="enlevement" name="enlevement" value="oui">
                            <label for="enlevement">🚚 Enlèvement à domicile (+frais supplémentaires)</label>
                        </div>

                        <!-- Boutons de navigation -->
                        <div class="form-nav-buttons">
                            <button type="button" class="btn-prev" data-goto="2">Précédent</button>
                            <button type="submit" id="calculateBtn" class="btn-calculate">
                                🧮 Calculer les tarifs
                            </button>
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
                    <p>Navigation étape par étape pour une comparaison précise des tarifs</p>
                    <div style="margin-top: 2rem; padding: 1rem; background: rgba(37, 99, 235, 0.05); border-radius: 0.5rem;">
                        <strong>Étapes :</strong><br>
                        1️⃣ Saisissez le département<br>
                        2️⃣ Indiquez le poids et le type d'envoi<br>
                        3️⃣ Configurez les options de livraison<br>
                        4️⃣ Lancez le calcul pour comparer les tarifs
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

<?php
// ========================================
// 🎨 CHARGEMENT FOOTER
// ========================================
include_once ROOT_PATH . '/templates/footer.php';
?>
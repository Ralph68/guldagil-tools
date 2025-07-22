<?php
/**
 * Titre: Calculateur de frais de port - Interface complète CORRIGÉE
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configuration et chemins
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';
require_once ROOT_PATH . '/config/error_handler_simple.php';

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
// 🔧 GESTION AJAX CALCULATE - VERSION CORRIGÉE
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
        
        // 🚨 CHARGEMENT DE LA VRAIE CLASSE TRANSPORT
        $transport_file = __DIR__ . '/calculs/transport.php';
        if (!file_exists($transport_file)) {
            throw new Exception('Classe Transport non trouvée: ' . $transport_file);
        }
        
        require_once $transport_file;
        
        if (!class_exists('Transport')) {
            throw new Exception('Classe Transport non chargée');
        }
        
        // Initialisation avec la bonne connexion DB
        $transport = new Transport($db);
        
        // ⏱️ CALCUL RÉEL DES TARIFS
        $start_time = microtime(true);
        $results = $transport->calculateAll($params);
        $calc_time = round((microtime(true) - $start_time) * 1000, 2);
        
        // 🎯 FORMATAGE DE LA RÉPONSE CORRECTE
        $response = [
            'success' => true,
            'carriers' => [],
            'time_ms' => $calc_time,
            'debug' => [
                'params_received' => $params,
                'transport_class' => get_class($transport),
                'calculators_loaded' => property_exists($transport, 'calculators') ? count($transport->calculators) : 0
            ]
        ];
        
        // Traitement des résultats par transporteur
        if (isset($results['results']) && is_array($results['results'])) {
            foreach ($results['results'] as $carrier => $result) {
                if ($result !== null && is_array($result)) {
                    // Format standardisé pour chaque transporteur
                    $response['carriers'][$carrier] = [
                        'prix_ht' => $result['prix_ht'] ?? 0,
                        'prix_ttc' => $result['prix_ttc'] ?? 0,
                        'delai' => $result['delai'] ?? 'N/A',
                        'service' => $result['service'] ?? 'Standard',
                        'details' => $result['details'] ?? []
                    ];
                }
            }
            
            // Ajout du debug si disponible
            if (isset($results['debug'])) {
                $response['debug']['transport_debug'] = $results['debug'];
            }
        }
        
        // 📊 ANALYSE DES RÉSULTATS
        $valid_results = array_filter($response['carriers'], function($result) {
            return isset($result['prix_ttc']) && $result['prix_ttc'] > 0;
        });
        
        if (empty($valid_results)) {
            $response['success'] = false;
            $response['error'] = 'Aucun transporteur disponible pour ces critères';
            $response['debug']['no_results_reason'] = 'Tous les transporteurs ont retourné des prix nuls ou invalides';
        }
        
        // Log pour débogage (optionnel)
        if (function_exists('logInfo')) {
            logInfo('Calcul transport', [
                'params' => $params,
                'results_count' => count($valid_results),
                'time_ms' => $calc_time
            ]);
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        // Gestion d'erreur robuste
        $error_response = [
            'success' => false,
            'error' => $e->getMessage(),
            'debug' => [
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'post_data' => $post_data ?? null
            ]
        ];
        
        // Log d'erreur (optionnel)
        if (function_exists('logError')) {
            logError('Erreur calcul transport', [
                'error' => $e->getMessage(),
                'params' => $post_data ?? null,
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        echo json_encode($error_response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ========================================
// 🎨 CHARGEMENT HEADER
// ========================================
include ROOT_PATH . '/templates/header.php';
?>

<!-- CSS spécifique module port via header.php automatique -->

<!-- Container principal avec classes CSS modernisées -->
<div class="calc-container">
    <main class="calc-main">
        <!-- EN-TÊTE DU MODULE -->
        <div class="calc-header">
            <h1>🚛 Calculateur de Frais de Port</h1>
            <p>Comparaison instantanée des tarifs XPO, Heppner et Kuehne+Nagel</p>
        </div>

        <!-- FORMULAIRE -->
        <section class="calc-form-panel">
            <!-- Étapes -->
            <div class="calc-steps">
                <button type="button" class="calc-step-btn active" data-step="1">
                    📍 Destination
                </button>
                <button type="button" class="calc-step-btn" data-step="2">
                    📦 Colis
                </button>
                <button type="button" class="calc-step-btn" data-step="3">
                    ⚙️ Options
                </button>
            </div>
            
            <!-- Contenu formulaire -->
            <div class="calc-form-content">
                <form id="calculatorForm" class="calc-form" novalidate>
                    <!-- Étape 1: Destination -->
                    <div class="calc-step-content active" data-step="1" style="display: block;">
                        <div class="calc-form-group">
                            <label for="departement" class="calc-label">
                                📍 Département de destination *
                            </label>
                            <input type="text" 
                                   id="departement" 
                                   name="departement" 
                                   class="calc-input" 
                                   placeholder="Ex: 75, 69, 13, 2A..."
                                   maxlength="3"
                                   required>
                            <small class="calc-help">
                                Saisissez le numéro du département (01-95, 2A, 2B)
                            </small>
                        </div>
                    </div>

                    <!-- Étape 2: Poids et Type -->
                    <div class="calc-step-content" data-step="2" style="display: none;">
                        <div class="calc-form-group">
                            <label for="poids" class="calc-label">
                                ⚖️ Poids total de l'envoi *
                            </label>
                            <div class="calc-input-group">
                                <input type="number" 
                                       id="poids" 
                                       name="poids" 
                                       class="calc-input" 
                                       placeholder="150"
                                       min="1" 
                                       max="3000" 
                                       step="0.1"
                                       required>
                                <span class="calc-input-suffix">kg</span>
                            </div>
                            <small class="calc-help">
                                Poids total de 1 à 3000 kg - Type suggéré automatiquement
                            </small>
                        </div>

                        <div class="calc-form-group">
                            <label for="type" class="calc-label">
                                📦 Type d'envoi *
                            </label>
                            <select id="type" name="type" class="calc-select" required>
                                <option value="">-- Sélection automatique --</option>
                                <option value="colis">📦 Colis (≤ 150kg)</option>
                                <option value="palette">🏗️ Palette (> 150kg)</option>
                            </select>
                        </div>

                        <!-- Options palettes (masquées par défaut) -->
                        <div class="calc-form-group" id="palettesGroup" style="display: none;">
                            <label for="palettes" class="calc-label">
                                🏗️ Nombre de palettes
                            </label>
                            <select id="palettes" name="palettes" class="calc-select">
                                <option value="1">1 palette</option>
                                <option value="2">2 palettes</option>
                                <option value="3">3 palettes</option>
                                <option value="4">4 palettes</option>
                            </select>
                        </div>

                        <div class="calc-form-group" id="paletteEurGroup" style="display: none;">
                            <label for="palette_eur" class="calc-label">
                                🔄 Palettes EUR consignées
                            </label>
                            <select id="palette_eur" name="palette_eur" class="calc-select">
                                <option value="0">Aucune</option>
                                <option value="1">1 palette EUR</option>
                                <option value="2">2 palettes EUR</option>
                                <option value="3">3 palettes EUR</option>
                                <option value="4">4 palettes EUR</option>
                            </select>
                            <small class="calc-help">
                                Palettes Europe à récupérer chez le destinataire
                            </small>
                        </div>
                    </div>

                    <!-- Étape 3: Options et Services -->
                    <div class="calc-step-content" data-step="3" style="display: none;">
                        <!-- ADR (Matières dangereuses) -->
                        <div class="calc-form-group">
                            <label class="calc-label">
                                ⚠️ Matières dangereuses (ADR) *
                            </label>
                            <div class="calc-toggle-group">
                                <button type="button" class="calc-toggle-btn" data-adr="non">
                                    ✅ Non - Transport standard
                                </button>
                                <button type="button" class="calc-toggle-btn" data-adr="oui">
                                    ⚠️ Oui - Matières dangereuses
                                </button>
                            </div>
                            <input type="hidden" id="adr" name="adr" value="">
                            <small class="calc-help">
                                Les matières dangereuses nécessitent un transport spécialisé ADR
                            </small>
                        </div>

                        <!-- Enlèvement -->
                        <div class="calc-form-group">
                            <label class="calc-label">
                                🚚 Type de service
                            </label>
                            <div class="calc-toggle-group">
                                <button type="button" class="calc-toggle-btn active" data-enlevement="non">
                                    📮 Livraison standard
                                </button>
                                <button type="button" class="calc-toggle-btn" data-enlevement="oui">
                                    🚚 Enlèvement + livraison
                                </button>
                            </div>
                            <input type="hidden" id="enlevement" name="enlevement" value="non">
                        </div>

                        <!-- Options supplémentaires -->
                        <div class="calc-form-group">
                            <label for="option_sup" class="calc-label">
                                ⚙️ Options supplémentaires
                            </label>
                            <select id="option_sup" name="option_sup" class="calc-select">
                                <option value="standard">Standard</option>
                                <option value="express">Express (+1 jour)</option>
                                <option value="urgent">Urgent (J+1)</option>
                            </select>
                        </div>

                        <!-- Bouton de calcul -->
                        <div class="calc-form-group">
                            <button type="submit" id="calculateBtn" class="calc-btn-primary">
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
                <div id="calcStatus" class="calc-status">
                    ⏳ En attente de vos paramètres...
                </div>
            </div>
            
            <div id="resultsContent" class="calc-results-content">
                <div class="calc-welcome">
                    <div class="calc-welcome-icon">🚛</div>
                    <h3>Bienvenue dans le calculateur</h3>
                    <p>Saisissez vos paramètres d'expédition pour obtenir une comparaison instantanée des tarifs de transport.</p>
                    
                    <div class="calc-features">
                        <div class="calc-feature">
                            <span class="calc-feature-icon">⚡</span>
                            <span>Calcul instantané</span>
                        </div>
                        <div class="calc-feature">
                            <span class="calc-feature-icon">🏆</span>
                            <span>Meilleur prix</span>
                        </div>
                        <div class="calc-feature">
                            <span class="calc-feature-icon">📋</span>
                            <span>Comparaison détaillée</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<?php
// ========================================
// 🎨 CHARGEMENT FOOTER
// ========================================
include ROOT_PATH . '/templates/footer.php';
?>

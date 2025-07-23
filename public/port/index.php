<?php
/**
 * Titre: Calculateur de frais de port - Version corrigée SESSION UNIQUEMENT
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 * CORRECTION MINIMALE: Ligne 37 - session_start() doublé
 */

// Configuration de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/version.php';

// Charger les fonctions helper pour les permissions
if (file_exists(ROOT_PATH . '/config/functions.php')) {
    require_once ROOT_PATH . '/config/functions.php';
} else {
    // Fallback des fonctions si fichier manquant
    if (!function_exists('canAccessModule')) {
        function canAccessModule($module_key, $module_data, $user_role) {
            return in_array($user_role, ['admin', 'dev']) || $module_key === 'port';
        }
    }
    if (!function_exists('shouldShowModule')) {
        function shouldShowModule($module_key, $module_data, $user_role) {
            return true;
        }
    }
}

// Variables pour header/footer - DÉFINIR AVANT session_start()
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

// ========================================
// 🔐 CORRECTION CRITIQUE - SESSION SÉCURISÉE
// ========================================
// ❌ LIGNE 37 ORIGINALE : session_start(); // Causait l'erreur "session already active"
// ✅ CORRECTION : Vérification préalable obligatoire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chargement header (qui gère l'authentification et les sessions)
require_once ROOT_PATH . '/templates/header.php';

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
        
        // Connexion BDD sécurisée
        try {
            $db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new Exception('Erreur base de données');
        }
        
        $transport = new Transport($db);
        
        $start_time = microtime(true);
        $results = $transport->calculateAll($params);
        $calc_time = round((microtime(true) - $start_time) * 1000, 2);
        
        $response = [
            'success' => true,
            'carriers' => [],
            'time_ms' => $calc_time,
            'debug' => $transport->debug ?? [],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Format des résultats
        foreach ($results as $carrier => $data) {
            if (isset($data['prix']) && is_numeric($data['prix'])) {
                $response['carriers'][$carrier] = [
                    'prix' => floatval($data['prix']),
                    'taxes' => floatval($data['taxes'] ?? 0),
                    'total' => floatval($data['total'] ?? $data['prix']),
                    'delai' => $data['delai'] ?? '2-3 jours',
                    'service' => $data['service'] ?? 'Standard',
                    'disponible' => true,
                    'details' => $data['details'] ?? []
                ];
            } else {
                $response['carriers'][$carrier] = [
                    'disponible' => false,
                    'raison' => $data['erreur'] ?? 'Service indisponible'
                ];
            }
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
?>

<div class="calc-container" id="calculatorApp">
    <!-- Header de l'application -->
    <header class="calc-header">
        <div class="calc-hero">
            <h1 class="calc-title">
                <span class="calc-icon">🚛</span>
                Calculateur de Frais de Port
            </h1>
            <p class="calc-subtitle">Comparaison instantanée des tarifs XPO, Heppner et Kuehne+Nagel</p>
            <div class="calc-features">
                <span class="feature-badge">⚡ Calcul en temps réel</span>
                <span class="feature-badge">📊 Comparaison multi-transporteurs</span>
                <span class="feature-badge">💼 Tarifs professionnels</span>
            </div>
        </div>
    </header>

    <main class="calc-main">
        <!-- Section Formulaire -->
        <section class="calc-form-section">
            <div class="calc-form-container">
                <form id="calculatorForm" class="calc-form">
                    <!-- En-tête du formulaire -->
                    <div class="calc-form-header">
                        <h2 class="calc-form-title">Paramètres d'envoi</h2>
                        <div class="calc-form-progress">
                            <div class="calc-progress-steps">
                                <div class="calc-step active" data-step="1">
                                    <span class="step-number">1</span>
                                    <span class="step-label">Destination</span>
                                </div>
                                <div class="calc-step" data-step="2">
                                    <span class="step-number">2</span>
                                    <span class="step-label">Colis</span>
                                </div>
                                <div class="calc-step" data-step="3">
                                    <span class="step-number">3</span>
                                    <span class="step-label">Options</span>
                                </div>
                            </div>
                            <div class="calc-progress-bar">
                                <div class="calc-progress-fill" style="width: 33%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Étapes du formulaire -->
                    <div class="calc-form-steps">
                        <!-- Étape 1: Destination -->
                        <div class="calc-step-content active" data-step="1">
                            <h3 class="calc-step-title">📍 Destination</h3>
                            
                            <div class="calc-field-group">
                                <label for="departement" class="calc-label">
                                    Département de livraison
                                    <span class="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="departement" 
                                    name="departement" 
                                    class="calc-input calc-input-highlight" 
                                    placeholder="Ex: 75, 69, 13, 974..."
                                    maxlength="3"
                                    required
                                    autocomplete="postal-code"
                                    aria-describedby="departementHelp departementError"
                                >
                                <div id="departementHelp" class="calc-field-help">
                                    Saisissez le numéro du département (2 ou 3 chiffres pour DOM-TOM)
                                </div>
                                <div id="departementError" class="calc-error" role="alert"></div>
                            </div>
                        </div>

                        <!-- Étape 2: Caractéristiques du colis -->
                        <div class="calc-step-content" data-step="2">
                            <h3 class="calc-step-title">📦 Caractéristiques du colis</h3>
                            
                            <div class="calc-field-group">
                                <label for="poids" class="calc-label">
                                    Poids total
                                    <span class="required">*</span>
                                </label>
                                <div class="calc-input-with-unit">
                                    <input 
                                        type="number" 
                                        id="poids" 
                                        name="poids" 
                                        class="calc-input calc-input-highlight" 
                                        placeholder="Ex: 25"
                                        min="0.1" 
                                        max="32000" 
                                        step="0.1"
                                        required
                                        aria-describedby="poidsHelp poidsError"
                                    >
                                    <span class="calc-input-unit">kg</span>
                                </div>
                                <div id="poidsHelp" class="calc-field-help">
                                    Poids total de votre envoi (0.1 à 32000 kg)
                                </div>
                                <div id="poidsError" class="calc-error" role="alert"></div>
                            </div>

                            <div class="calc-field-group">
                                <label for="type" class="calc-label">Type d'envoi</label>
                                <select id="type" name="type" class="calc-select">
                                    <option value="colis">📦 Colis standard</option>
                                    <option value="palette">🏗️ Palette(s)</option>
                                    <option value="messagerie">🚚 Messagerie</option>
                                </select>
                            </div>

                            <!-- Champs conditionnels pour palettes -->
                            <div id="palettesGroup" class="calc-field-group calc-conditional" style="display: none;">
                                <label for="palettes" class="calc-label">Nombre de palettes</label>
                                <input 
                                    type="number" 
                                    id="palettes" 
                                    name="palettes" 
                                    class="calc-input" 
                                    value="1" 
                                    min="1" 
                                    max="26"
                                >
                            </div>

                            <div id="paletteEurGroup" class="calc-field-group calc-conditional" style="display: none;">
                                <label for="palette_eur" class="calc-label">Palettes EUR</label>
                                <input 
                                    type="number" 
                                    id="palette_eur" 
                                    name="palette_eur" 
                                    class="calc-input" 
                                    value="0" 
                                    min="0" 
                                    max="26"
                                >
                                <div class="calc-field-help">
                                    Nombre de palettes européennes (80x120cm)
                                </div>
                            </div>
                        </div>

                        <!-- Étape 3: Options avancées -->
                        <div class="calc-step-content" data-step="3">
                            <h3 class="calc-step-title">⚙️ Options et services</h3>
                            
                            <!-- Sélection ADR -->
                            <div class="calc-field-group">
                                <fieldset class="calc-fieldset">
                                    <legend class="calc-legend">Type de marchandise</legend>
                                    <div class="calc-radio-group">
                                        <label class="calc-radio-card">
                                            <input type="radio" name="adr" value="non" checked>
                                            <div class="calc-radio-content">
                                                <div class="calc-radio-icon">📦</div>
                                                <div class="calc-radio-text">
                                                    <strong>Colis standard</strong>
                                                    <span>Marchandise non dangereuse</span>
                                                </div>
                                            </div>
                                        </label>

                                        <label class="calc-radio-card">
                                            <input type="radio" name="adr" value="oui">
                                            <div class="calc-radio-content">
                                                <div class="calc-radio-icon">⚠️</div>
                                                <div class="calc-radio-text">
                                                    <strong>Matière dangereuse ADR</strong>
                                                    <span>Produits chimiques, gaz, etc.</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </fieldset>
                            </div>

                            <!-- Service de transport -->
                            <div class="calc-field-group">
                                <label for="option_sup" class="calc-label">Service de transport</label>
                                <select id="option_sup" name="option_sup" class="calc-select">
                                    <option value="standard">🚚 Standard (2-3 jours)</option>
                                    <option value="express">⚡ Express (24h)</option>
                                    <option value="eco">🌱 Économique (4-5 jours)</option>
                                </select>
                            </div>

                            <!-- Options supplémentaires -->
                            <div class="calc-field-group">
                                <div class="calc-checkbox-group">
                                    <label class="calc-checkbox-card">
                                        <input type="checkbox" id="enlevement" name="enlevement" value="oui">
                                        <div class="calc-checkbox-content">
                                            <div class="calc-checkbox-icon">🏠</div>
                                            <div class="calc-checkbox-text">
                                                <strong>Enlèvement à domicile</strong>
                                                <span>Service de collecte sur site</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions du formulaire -->
                    <div class="calc-form-actions">
                        <button type="button" id="prevBtn" class="calc-btn calc-btn-secondary" style="display: none;">
                            ← Précédent
                        </button>
                        <button type="button" id="nextBtn" class="calc-btn calc-btn-primary">
                            Suivant →
                        </button>
                        <button type="submit" id="calculateBtn" class="calc-btn calc-btn-success" style="display: none;">
                            🧮 Calculer les tarifs
                        </button>
                    </div>

                    <!-- Status -->
                    <div id="calcStatus" class="calc-status"></div>
                </form>
            </div>
        </section>

        <!-- Section Résultats -->
        <section class="calc-results-section">
            <div class="calc-results-container">
                <!-- Zone de résultats par défaut -->
                <div id="resultsContent" class="calc-results-placeholder">
                    <div class="calc-placeholder-content">
                        <div class="calc-placeholder-icon">🧮</div>
                        <h3>Résultats de calcul</h3>
                        <p>Complétez le formulaire pour voir les tarifs de transport</p>
                        <div class="calc-placeholder-steps">
                            <div class="placeholder-step">
                                <span class="step-icon">📍</span>
                                <span>Destination</span>
                            </div>
                            <div class="placeholder-step">
                                <span class="step-icon">📦</span>
                                <span>Colis</span>
                            </div>
                            <div class="placeholder-step">
                                <span class="step-icon">⚙️</span>
                                <span>Options</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conteneur des résultats réels -->
                <div id="resultsContainer" class="calc-results-grid" style="display: none;">
                    <!-- Les résultats s'affichent ici via JavaScript -->
                </div>
            </div>

            <!-- Infos complémentaires -->
            <div class="calc-info-section">
                <div class="calc-section calc-about">
                    <div class="calc-section-header" onclick="toggleAbout()">
                        <span>ℹ️ À propos du calculateur</span>
                        <span class="calc-toggle-icon" id="aboutToggle">▼</span>
                    </div>
                    <div class="calc-section-content" id="aboutContent">
                        <p>Ce calculateur compare automatiquement les tarifs de nos partenaires transporteurs :</p>
                        <ul class="calc-carriers-list">
                            <li><strong>XPO Logistics</strong> - Spécialiste messagerie et palettes</li>
                            <li><strong>Heppner</strong> - Expert transport France et Europe</li>
                            <li><strong>Kuehne+Nagel</strong> - Solutions logistiques intégrées</li>
                        </ul>
                        <p><strong>Avantages :</strong></p>
                        <ul>
                            <li>✅ Calcul en temps réel des tarifs négociés</li>
                            <li>✅ Comparaison automatique pour le meilleur prix</li>
                            <li>✅ Gestion des matières dangereuses ADR</li>
                            <li>✅ Options de services personnalisables</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Section Express dédié -->
                <div class="calc-section calc-express">
                    <div class="calc-section-header" onclick="toggleExpress()">
                        <span>⚡ Express dédié 12h</span>
                        <span class="calc-toggle-icon" id="expressToggle">▼</span>
                    </div>
                    <div class="calc-section-content" id="expressContent">
                        <p>Pour les livraisons urgentes, nous proposons un service express dédié.</p>
                        <p><strong>Caractéristiques :</strong></p>
                        <ul>
                            <li>🚚 Véhicule dédié exclusivement à votre envoi</li>
                            <li>⏰ Livraison garantie sous 12h en France métropolitaine</li>
                            <li>📍 Suivi GPS en temps réel</li>
                            <li>🔄 Prise en charge immédiate</li>
                        </ul>
                        <p class="calc-express-note">
                        Il permet de débloquer les situations critiques avec une livraison garantie sous 12h.</p>
                        <div class="calc-express-toggle">
                            <button type="button" class="calc-express-btn" onclick="contactExpress()">
                                ⚡ Demander Express Dédié <span>→</span>
                            </button>
                        </div>
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
            </div>
        </section>
    </main>
</div>

<?php
require_once ROOT_PATH . '/templates/footer.php';
?>

<!-- JavaScript du module -->
<script src="assets/js/port.js?v=<?= $build_number ?>"></script>

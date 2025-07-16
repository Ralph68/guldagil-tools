<?php
/**
 * Titre: Calculateur de frais de port - Interface complète avec headers corrigés
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

// CRITIQUES : Aucun output avant cette ligne !
// Configuration d'erreurs AVANT tout output
ini_set('display_errors', 1);
error_reporting(E_ALL);

// GESTION AJAX CALCULATE - AVANT TOUT AUTRE OUTPUT
if (isset($_GET['ajax']) && $_GET['ajax'] === 'calculate') {
    header('Content-Type: application/json');
    
    // Configuration
    define('ROOT_PATH', dirname(dirname(__DIR__)));
    require_once __DIR__ . '/../../config/config.php';
    
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
        
        // NOUVELLE LOGIQUE : Si poids > 60kg, forcer type palette
        if ($params['poids'] > 60) {
            $params['type'] = 'palette';
            $params['palettes'] = max(1, ceil($params['poids'] / 300));
        }
        
        if (empty($params['departement']) || !preg_match('/^[0-9]{2,3}$/', $params['departement'])) {
            throw new Exception('Département invalide');
        }
        if ($params['poids'] <= 0 || $params['poids'] > 3000) {
            throw new Exception('Poids invalide (0.1kg à 3000kg maximum)');
        }
        
        // Vérification limites palettes
        if ($params['type'] === 'palette' && $params['palettes'] > 6) {
            throw new Exception('Maximum 6 palettes. Au-delà, contactez-nous pour une cotation affrètement.');
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
    
    // Configuration si pas encore définie
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', dirname(dirname(__DIR__)));
        require_once __DIR__ . '/../../config/config.php';
    }
    
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

// GESTION AJAX AFFRETEMENT
if (isset($_GET['ajax']) && $_GET['ajax'] === 'affretement') {
    header('Content-Type: application/json');
    
    try {
        parse_str(file_get_contents('php://input'), $post_data);
        
        // Validation des données requises
        $required_fields = ['cp_depart', 'ville_depart', 'cp_arrivee', 'ville_arrivee', 
                           'poids_total', 'nb_palettes', 'date_souhaite', 'contact_depart', 'contact_arrivee'];
        
        foreach ($required_fields as $field) {
            if (empty($post_data[$field])) {
                throw new Exception("Le champ {$field} est obligatoire");
            }
        }
        
        // Construction du mail
        $subject = "Demande d'affrètement - " . $post_data['ville_depart'] . " → " . $post_data['ville_arrivee'];
        
        $body = "DEMANDE D'AFFRÈTEMENT\n";
        $body .= "========================\n\n";
        
        $body .= "TRAJET :\n";
        $body .= "📤 Départ : " . $post_data['cp_depart'] . " " . $post_data['ville_depart'] . "\n";
        $body .= "📥 Arrivée : " . $post_data['cp_arrivee'] . " " . $post_data['ville_arrivee'] . "\n\n";
        
        $body .= "MARCHANDISE :\n";
        $body .= "⚖️ Poids total : " . $post_data['poids_total'] . " kg\n";
        $body .= "📦 Nombre de palettes : " . $post_data['nb_palettes'] . "\n";
        $body .= "⚠️ ADR : " . ($post_data['adr'] === 'oui' ? 'OUI' : 'NON') . "\n";
        
        if ($post_data['adr'] === 'oui' && !empty($post_data['adr_details'])) {
            $body .= "   Détails ADR : " . $post_data['adr_details'] . "\n";
        }
        $body .= "\n";
        
        $body .= "PLANNING :\n";
        $body .= "📅 Date souhaitée : " . $post_data['date_souhaite'] . "\n";
        $body .= "🔄 Flexibilité : " . ($post_data['flexibilite'] ?? 'Non précisée') . "\n\n";
        
        $body .= "CONTACTS :\n";
        $body .= "📞 Contact départ : " . $post_data['contact_depart'] . "\n";
        $body .= "📞 Contact arrivée : " . $post_data['contact_arrivee'] . "\n\n";
        
        $body .= "CONTRAINTES :\n";
        $body .= "🚛 Hayon obligatoire : " . ($post_data['hayon_obligatoire'] === 'oui' ? 'OUI' : 'NON') . "\n";
        
        if (!empty($post_data['contraintes_specifiques'])) {
            $body .= "⚙️ Contraintes : " . $post_data['contraintes_specifiques'] . "\n";
        }
        
        if (!empty($post_data['commentaires'])) {
            $body .= "\nCOMMENTAIRES :\n" . $post_data['commentaires'] . "\n";
        }
        
        $body .= "\n---\nDemande générée depuis le portail interne GUL";
        $body .= "\nDate : " . date('d/m/Y H:i:s');
        
        // Headers email
        $headers = [
            'From: portail@guldaigl.com',
            'Reply-To: achats@guldaigl.com',
            'X-Mailer: PHP/' . phpversion(),
            'Content-Type: text/plain; charset=UTF-8'
        ];
        
        // Envoi du mail
        $mail_sent = mail('achats@guldaigl.com', $subject, $body, implode("\r\n", $headers));
        
        if ($mail_sent) {
            echo json_encode([
                'success' => true,
                'message' => 'Demande d\'affrètement envoyée avec succès'
            ]);
        } else {
            throw new Exception('Erreur lors de l\'envoi du mail');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// ========================================
// PAGE NORMALE - APRÈS TOUTES LES AJAX
// ========================================

// Configuration
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

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

// NE PAS faire session_start() ici - le header s'en occupe !
// Chargement header (qui gère l'authentification et les sessions)
require_once ROOT_PATH . '/templates/header.php';
?>

<div class="calc-container">
    
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
                               min="0.1" max="3000" step="0.1" placeholder="Ex: 25.5" required>
                        <small class="calc-help">Entre 0.1 et <strong>3000 kg maximum</strong>. Si > 60kg = automatiquement palette</small>
                    </div>
                    
                    <!-- Message limite poids -->
                    <div class="calc-limit-warning" id="limitWarning">
                        <div class="calc-limit-icon">⚖️</div>
                        <div class="calc-limit-title">Limite dépassée</div>
                        <div class="calc-limit-text">
                            Au-delà de 3000kg ou 6 palettes, nous devons établir une cotation personnalisée pour l'affrètement.
                        </div>
                        <div class="calc-limit-actions">
                            <a href="tel:+33389634242" class="calc-btn-contact">
                                📞 Appeler 03 89 63 42 42
                            </a>
                            <button type="button" class="calc-btn-contact" onclick="showAffretement()">
                                📋 Formulaire affrètement
                            </button>
                        </div>
                    </div>
                    
                    <div class="calc-form-group">
                        <label class="calc-label" for="type">Type d'expédition *</label>
                        <select id="type" name="type" class="calc-input" required>
                            <option value="">Choisir...</option>
                            <option value="colis">📦 Colis (≤ 60kg)</option>
                            <option value="palette">🏗️ Palette(s)</option>
                        </select>
                        <small class="calc-help">🔄 Choix automatique si poids > 60kg</small>
                    </div>
                    
                    <div class="calc-form-group calc-group-palettes" id="palettesGroup" style="display: none;">
                        <label class="calc-label" for="palettes">Nombre de palettes</label>
                        <input type="number" id="palettes" name="palettes" class="calc-input" 
                               min="1" max="6" value="1">
                        <small class="calc-help">🧮 Calcul automatique selon poids (1 palette ≈ 300kg max). <strong>Maximum 6 palettes</strong></small>
                    </div>
                    
                    <!-- Message limite palettes -->
                    <div class="calc-limit-warning" id="limitPalettesWarning">
                        <div class="calc-limit-icon">🚛</div>
                        <div class="calc-limit-title">Affrètement nécessaire</div>
                        <div class="calc-limit-text">
                            Plus de 6 palettes nécessite un transport dédié avec cotation spécifique.
                        </div>
                        <div class="calc-limit-actions">
                            <a href="tel:+33389634242" class="calc-btn-contact">
                                📞 Appeler 03 89 63 42 42
                            </a>
                            <button type="button" class="calc-btn-contact" onclick="showAffretement()">
                                📋 Formulaire affrètement
                            </button>
                        </div>
                    </div>
                    
                    <div class="calc-form-group calc-group-palette-eur" id="paletteEurGroup" style="display: none;">
                        <label class="calc-label" for="palette_eur">
                            🏷️ Palettes EUR consignées
                            <span class="calc-label-optional">- Facultatif</span>
                        </label>
                        <input type="number" id="palette_eur" name="palette_eur" class="calc-input" 
                               min="0" value="0" step="1" placeholder="Nombre de palettes EUR">
                        <small class="calc-help calc-help-palette">
                            💡 <strong>Palette EUR ≠ Palette normale</strong><br>
                            • <strong>0 = palette perdue</strong> (économise 1,80€ de consigne XPO par palette)<br>
                            • <strong>X = palettes retournées</strong> (consigne XPO à 1,80€/palette)
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
        
        <!-- FORMULAIRE AFFRÈTEMENT SIMPLIFIÉ -->
        <section class="calc-affretement-panel" id="affretementPanel" style="display: none;">
            <div class="calc-affret-header">
                <div class="calc-affret-icon">🚛</div>
                <div>
                    <h2 class="calc-affret-title">Demande d'Affrètement</h2>
                    <p class="calc-affret-subtitle">Transport > 3000kg ou > 6 palettes</p>
                </div>
                <button type="button" class="calc-affret-close" onclick="closeAffretement()">✕</button>
            </div>
            
            <form id="affretementForm" class="calc-affret-form">
                <!-- TRAJET -->
                <div class="calc-affret-section">
                    <h3 class="calc-affret-section-title">🗺️ Trajet</h3>
                    <div class="calc-affret-grid">
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_cp_depart">Code postal départ *</label>
                            <input type="text" id="affret_cp_depart" name="cp_depart" class="calc-input" 
                                   placeholder="67000" pattern="[0-9]{5}" maxlength="5" required>
                        </div>
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_ville_depart">Ville départ *</label>
                            <input type="text" id="affret_ville_depart" name="ville_depart" class="calc-input" 
                                   placeholder="Strasbourg" required>
                        </div>
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_cp_arrivee">Code postal arrivée *</label>
                            <input type="text" id="affret_cp_arrivee" name="cp_arrivee" class="calc-input" 
                                   placeholder="75001" pattern="[0-9]{5}" maxlength="5" required>
                        </div>
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_ville_arrivee">Ville arrivée *</label>
                            <input type="text" id="affret_ville_arrivee" name="ville_arrivee" class="calc-input" 
                                   placeholder="Paris" required>
                        </div>
                    </div>
                </div>
                
                <!-- MARCHANDISE -->
                <div class="calc-affret-section">
                    <h3 class="calc-affret-section-title">📦 Marchandise</h3>
                    <div class="calc-affret-grid">
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_poids">Poids total (kg) *</label>
                            <input type="number" id="affret_poids" name="poids_total" class="calc-input" 
                                   min="1" step="0.1" placeholder="3500" required>
                        </div>
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_palettes">Nombre de palettes *</label>
                            <input type="number" id="affret_palettes" name="nb_palettes" class="calc-input" 
                                   min="1" placeholder="8" required>
                        </div>
                    </div>
                    
                    <div class="calc-form-group">
                        <label class="calc-label">Transport ADR (matières dangereuses) *</label>
                        <div class="calc-toggle-group">
                            <button type="button" class="calc-toggle-btn active" data-affret-adr="non">Non</button>
                            <button type="button" class="calc-toggle-btn" data-affret-adr="oui">Oui</button>
                        </div>
                        <input type="hidden" id="affret_adr" name="adr" value="non">
                    </div>
                    
                    <div class="calc-form-group" id="affretAdrDetails" style="display: none;">
                        <label class="calc-label" for="affret_adr_details">Précisez le type de matières dangereuses *</label>
                        <textarea id="affret_adr_details" name="adr_details" class="calc-textarea" 
                                 placeholder="Ex: Classe 3 - Liquides inflammables - UN1993" rows="2"></textarea>
                    </div>
                </div>
                
                <!-- PLANNING ET CONTACTS -->
                <div class="calc-affret-section">
                    <h3 class="calc-affret-section-title">📅 Planning & Contacts</h3>
                    <div class="calc-affret-grid">
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_date_souhaite">Date souhaitée *</label>
                            <input type="date" id="affret_date_souhaite" name="date_souhaite" class="calc-input" required>
                        </div>
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_flexibilite">Flexibilité</label>
                            <select id="affret_flexibilite" name="flexibilite" class="calc-input">
                                <option value="aucune">Date impérative</option>
                                <option value="1-2j">+/- 1-2 jours</option>
                                <option value="1sem">+/- 1 semaine</option>
                                <option value="flexible">Flexible</option>
                            </select>
                        </div>
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_contact_depart">Contact départ *</label>
                            <input type="text" id="affret_contact_depart" name="contact_depart" class="calc-input" 
                                   placeholder="Nom + téléphone" required>
                        </div>
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_contact_arrivee">Contact arrivée *</label>
                            <input type="text" id="affret_contact_arrivee" name="contact_arrivee" class="calc-input" 
                                   placeholder="Nom + téléphone" required>
                        </div>
                    </div>
                </div>
                
                <!-- CONTRAINTES -->
                <div class="calc-affret-section">
                    <h3 class="calc-affret-section-title">⚙️ Contraintes</h3>
                    
                    <div class="calc-form-group">
                        <label class="calc-label">Hayon obligatoire *</label>
                        <div class="calc-toggle-group">
                            <button type="button" class="calc-toggle-btn active" data-affret-hayon="non">Non</button>
                            <button type="button" class="calc-toggle-btn" data-affret-hayon="oui">Oui</button>
                        </div>
                        <input type="hidden" id="affret_hayon" name="hayon_obligatoire" value="non">
                    </div>
                    
                    <div class="calc-form-group">
                        <label class="calc-label" for="affret_contraintes">Contraintes spécifiques</label>
                        <textarea id="affret_contraintes" name="contraintes_specifiques" class="calc-textarea" 
                                 placeholder="Ex: Restrictions horaires, accès difficile, produit fragile..." rows="3"></textarea>
                    </div>
                    
                    <div class="calc-form-group">
                        <label class="calc-label" for="affret_commentaires">Commentaires</label>
                        <textarea id="affret_commentaires" name="commentaires" class="calc-textarea" 
                                 placeholder="Informations complémentaires..." rows="3"></textarea>
                    </div>
                </div>
                
                <!-- ACTIONS -->
                <div class="calc-affret-actions">
                    <button type="submit" class="calc-btn-primary calc-btn-large">
                        📧 Envoyer demande
                    </button>
                    <button type="button" class="calc-btn-secondary" onclick="closeAffretement()">
                        🔄 Retour
                    </button>
                    <button type="button" class="calc-btn-contact" onclick="mailLibre()">
                        ✉️ Email libre
                    </button>
                </div>
            </form>
        </section>
        
        <!-- RÉSULTATS -->
        <section class="calc-results-panel" id="resultsPanel">
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
            
            <!-- Information Express Dédié -->
            <div class="calc-express-info">
                <div class="calc-express-header">
                    <div class="calc-express-icon">⚡</div>
                    <div>
                        <div class="calc-express-title">Express Dédié Disponible</div>
                        <div class="calc-express-subtitle">Livraison urgente 12h - Tarif au réel</div>
                    </div>
                </div>
                <div class="calc-express-content">
                    <p>Pour les situations d'urgence, nous proposons un <strong>service express dédié</strong> :</p>
                    <div class="calc-express-example">
                        📦 <strong>Exemple :</strong> Client en rupture de stock<br>
                        🕐 <strong>Délai :</strong> Chargé l'après-midi → Livré lendemain 8h<br>
                        💰 <strong>Coût :</strong> <span class="calc-express-price">600€ - 800€</span> (selon distance)
                    </div>
                    <p>Ce service est <strong>calculé au réel</strong> selon la distance et l'urgence. Il permet de débloquer les situations critiques avec une livraison garantie sous 12h.</p>
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
        </section>
    </main>
</div>

<script src="assets/js/port.js?v=<?= $version_info['build'] ?>"></script>

<?php
require_once ROOT_PATH . '/templates/footer.php';
?>
                <?php
/**
 * Titre: Calculateur de frais de port - Interface complète avec headers corrigés
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

// CRITIQUES : Aucun output avant cette ligne !
// Configuration d'erreurs AVANT tout output
ini_set('display_errors', 1);
error_reporting(E_ALL);

// GESTION AJAX CALCULATE - AVANT TOUT AUTRE OUTPUT
if (isset($_GET['ajax']) && $_GET['ajax'] === 'calculate') {
    header('Content-Type: application/json');
    
    // Configuration
    define('ROOT_PATH', dirname(dirname(__DIR__)));
    require_once __DIR__ . '/../../config/config.php';
    
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
        
        // NOUVELLE LOGIQUE : Si poids > 60kg, forcer type palette
        if ($params['poids'] > 60) {
            $params['type'] = 'palette';
            $params['palettes'] = max(1, ceil($params['poids'] / 300));
        }
        
        if (empty($params['departement']) || !preg_match('/^[0-9]{2,3}$/', $params['departement'])) {
            throw new Exception('Département invalide');
        }
        if ($params['poids'] <= 0 || $params['poids'] > 3000) {
            throw new Exception('Poids invalide (0.1kg à 3000kg maximum)');
        }
        
        // Vérification limites palettes
        if ($params['type'] === 'palette' && $params['palettes'] > 6) {
            throw new Exception('Maximum 6 palettes. Au-delà, contactez-nous pour une cotation affrètement.');
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
    
    // Configuration si pas encore définie
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', dirname(dirname(__DIR__)));
        require_once __DIR__ . '/../../config/config.php';
    }
    
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

// GESTION AJAX AFFRETEMENT
if (isset($_GET['ajax']) && $_GET['ajax'] === 'affretement') {
    header('Content-Type: application/json');
    
    try {
        parse_str(file_get_contents('php://input'), $post_data);
        
        // Validation des données requises
        $required_fields = ['cp_depart', 'ville_depart', 'cp_arrivee', 'ville_arrivee', 
                           'poids_total', 'nb_palettes', 'date_souhaite', 'contact_depart', 'contact_arrivee'];
        
        foreach ($required_fields as $field) {
            if (empty($post_data[$field])) {
                throw new Exception("Le champ {$field} est obligatoire");
            }
        }
        
        // Construction du mail
        $subject = "Demande d'affrètement - " . $post_data['ville_depart'] . " → " . $post_data['ville_arrivee'];
        
        $body = "DEMANDE D'AFFRÈTEMENT\n";
        $body .= "========================\n\n";
        
        $body .= "TRAJET :\n";
        $body .= "📤 Départ : " . $post_data['cp_depart'] . " " . $post_data['ville_depart'] . "\n";
        $body .= "📥 Arrivée : " . $post_data['cp_arrivee'] . " " . $post_data['ville_arrivee'] . "\n\n";
        
        $body .= "MARCHANDISE :\n";
        $body .= "⚖️ Poids total : " . $post_data['poids_total'] . " kg\n";
        $body .= "📦 Nombre de palettes : " . $post_data['nb_palettes'] . "\n";
        $body .= "⚠️ ADR : " . ($post_data['adr'] === 'oui' ? 'OUI' : 'NON') . "\n";
        
        if ($post_data['adr'] === 'oui' && !empty($post_data['adr_details'])) {
            $body .= "   Détails ADR : " . $post_data['adr_details'] . "\n";
        }
        $body .= "\n";
        
        $body .= "PLANNING :\n";
        $body .= "📅 Date souhaitée : " . $post_data['date_souhaite'] . "\n";
        $body .= "🔄 Flexibilité : " . ($post_data['flexibilite'] ?? 'Non précisée') . "\n\n";
        
        $body .= "CONTACTS :\n";
        $body .= "📞 Contact départ : " . $post_data['contact_depart'] . "\n";
        $body .= "📞 Contact arrivée : " . $post_data['contact_arrivee'] . "\n\n";
        
        $body .= "CONTRAINTES :\n";
        $body .= "🚛 Hayon obligatoire : " . ($post_data['hayon_obligatoire'] === 'oui' ? 'OUI' : 'NON') . "\n";
        
        if (!empty($post_data['contraintes_specifiques'])) {
            $body .= "⚙️ Contraintes : " . $post_data['contraintes_specifiques'] . "\n";
        }
        
        if (!empty($post_data['commentaires'])) {
            $body .= "\nCOMMENTAIRES :\n" . $post_data['commentaires'] . "\n";
        }
        
        $body .= "\n---\nDemande générée depuis le portail interne GUL";
        $body .= "\nDate : " . date('d/m/Y H:i:s');
        
        // Headers email
        $headers = [
            'From: portail@guldaigl.com',
            'Reply-To: achats@guldaigl.com',
            'X-Mailer: PHP/' . phpversion(),
            'Content-Type: text/plain; charset=UTF-8'
        ];
        
        // Envoi du mail
        $mail_sent = mail('achats@guldaigl.com', $subject, $body, implode("\r\n", $headers));
        
        if ($mail_sent) {
            echo json_encode([
                'success' => true,
                'message' => 'Demande d\'affrètement envoyée avec succès'
            ]);
        } else {
            throw new Exception('Erreur lors de l\'envoi du mail');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// ========================================
// PAGE NORMALE - APRÈS TOUTES LES AJAX
// ========================================

// Configuration
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';

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

// NE PAS faire session_start() ici - le header s'en occupe !
// Chargement header (qui gère l'authentification et les sessions)
require_once ROOT_PATH . '/templates/header.php';
?>

<div class="calc-container">
    
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
                               min="0.1" max="3000" step="0.1" placeholder="Ex: 25.5" required>
                        <small class="calc-help">Entre 0.1 et <strong>3000 kg maximum</strong>. Si > 60kg = automatiquement palette</small>
                    </div>
                    
                    <!-- Message limite poids -->
                    <div class="calc-limit-warning" id="limitWarning">
                        <div class="calc-limit-icon">⚖️</div>
                        <div class="calc-limit-title">Limite dépassée</div>
                        <div class="calc-limit-text">
                            Au-delà de 3000kg ou 6 palettes, nous devons établir une cotation personnalisée pour l'affrètement.
                        </div>
                        <div class="calc-limit-actions">
                            <a href="tel:+33389634242" class="calc-btn-contact">
                                📞 Appeler 03 89 63 42 42
                            </a>
                            <button type="button" class="calc-btn-contact" onclick="showAffretement()">
                                📋 Formulaire affrètement
                            </button>
                        </div>
                    </div>
                    
                    <div class="calc-form-group">
                        <label class="calc-label" for="type">Type d'expédition *</label>
                        <select id="type" name="type" class="calc-input" required>
                            <option value="">Choisir...</option>
                            <option value="colis">📦 Colis (≤ 60kg)</option>
                            <option value="palette">🏗️ Palette(s)</option>
                        </select>
                        <small class="calc-help">🔄 Choix automatique si poids > 60kg</small>
                    </div>
                    
                    <div class="calc-form-group calc-group-palettes" id="palettesGroup" style="display: none;">
                        <label class="calc-label" for="palettes">Nombre de palettes</label>
                        <input type="number" id="palettes" name="palettes" class="calc-input" 
                               min="1" max="6" value="1">
                        <small class="calc-help">🧮 Calcul automatique selon poids (1 palette ≈ 300kg max). <strong>Maximum 6 palettes</strong></small>
                    </div>
                    
                    <!-- Message limite palettes -->
                    <div class="calc-limit-warning" id="limitPalettesWarning">
                        <div class="calc-limit-icon">🚛</div>
                        <div class="calc-limit-title">Affrètement nécessaire</div>
                        <div class="calc-limit-text">
                            Plus de 6 palettes nécessite un transport dédié avec cotation spécifique.
                        </div>
                        <div class="calc-limit-actions">
                            <a href="tel:+33389634242" class="calc-btn-contact">
                                📞 Appeler 03 89 63 42 42
                            </a>
                            <button type="button" class="calc-btn-contact" onclick="showAffretement()">
                                📋 Formulaire affrètement
                            </button>
                        </div>
                    </div>
                    
                    <div class="calc-form-group calc-group-palette-eur" id="paletteEurGroup" style="display: none;">
                        <label class="calc-label" for="palette_eur">
                            🏷️ Palettes EUR consignées
                            <span class="calc-label-optional">- Facultatif</span>
                        </label>
                        <input type="number" id="palette_eur" name="palette_eur" class="calc-input" 
                               min="0" value="0" step="1" placeholder="Nombre de palettes EUR">
                        <small class="calc-help calc-help-palette">
                            💡 <strong>Palette EUR ≠ Palette normale</strong><br>
                            • <strong>0 = palette perdue</strong> (économise 1,80€ de consigne XPO par palette)<br>
                            • <strong>X = palettes retournées</strong> (consigne XPO à 1,80€/palette)
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
        
        <!-- FORMULAIRE AFFRÈTEMENT SIMPLIFIÉ -->
        <section class="calc-affretement-panel" id="affretementPanel" style="display: none;">
            <div class="calc-affret-header">
                <div class="calc-affret-icon">🚛</div>
                <div>
                    <h2 class="calc-affret-title">Demande d'Affrètement</h2>
                    <p class="calc-affret-subtitle">Transport > 3000kg ou > 6 palettes</p>
                </div>
                <button type="button" class="calc-affret-close" onclick="closeAffretement()">✕</button>
            </div>
            
            <form id="affretementForm" class="calc-affret-form">
                <!-- TRAJET -->
                <div class="calc-affret-section">
                    <h3 class="calc-affret-section-title">🗺️ Trajet</h3>
                    <div class="calc-affret-grid">
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_cp_depart">Code postal départ *</label>
                            <input type="text" id="affret_cp_depart" name="cp_depart" class="calc-input" 
                                   placeholder="67000" pattern="[0-9]{5}" maxlength="5" required>
                        </div>
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_ville_depart">Ville départ *</label>
                            <input type="text" id="affret_ville_depart" name="ville_depart" class="calc-input" 
                                   placeholder="Strasbourg" required>
                        </div>
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_cp_arrivee">Code postal arrivée *</label>
                            <input type="text" id="affret_cp_arrivee" name="cp_arrivee" class="calc-input" 
                                   placeholder="75001" pattern="[0-9]{5}" maxlength="5" required>
                        </div>
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_ville_arrivee">Ville arrivée *</label>
                            <input type="text" id="affret_ville_arrivee" name="ville_arrivee" class="calc-input" 
                                   placeholder="Paris" required>
                        </div>
                    </div>
                </div>
                
                <!-- MARCHANDISE -->
                <div class="calc-affret-section">
                    <h3 class="calc-affret-section-title">📦 Marchandise</h3>
                    <div class="calc-affret-grid">
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_poids">Poids total (kg) *</label>
                            <input type="number" id="affret_poids" name="poids_total" class="calc-input" 
                                   min="1" step="0.1" placeholder="3500" required>
                        </div>
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_palettes">Nombre de palettes *</label>
                            <input type="number" id="affret_palettes" name="nb_palettes" class="calc-input" 
                                   min="1" placeholder="8" required>
                        </div>
                    </div>
                    
                    <div class="calc-form-group">
                        <label class="calc-label">Transport ADR (matières dangereuses) *</label>
                        <div class="calc-toggle-group">
                            <button type="button" class="calc-toggle-btn active" data-affret-adr="non">Non</button>
                            <button type="button" class="calc-toggle-btn" data-affret-adr="oui">Oui</button>
                        </div>
                        <input type="hidden" id="affret_adr" name="adr" value="non">
                    </div>
                    
                    <div class="calc-form-group" id="affretAdrDetails" style="display: none;">
                        <label class="calc-label" for="affret_adr_details">Précisez le type de matières dangereuses *</label>
                        <textarea id="affret_adr_details" name="adr_details" class="calc-textarea" 
                                 placeholder="Ex: Classe 3 - Liquides inflammables - UN1993" rows="2"></textarea>
                    </div>
                </div>
                
                <!-- PLANNING ET CONTACTS -->
                <div class="calc-affret-section">
                    <h3 class="calc-affret-section-title">📅 Planning & Contacts</h3>
                    <div class="calc-affret-grid">
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_date_souhaite">Date souhaitée *</label>
                            <input type="date" id="affret_date_souhaite" name="date_souhaite" class="calc-input" required>
                        </div>
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_flexibilite">Flexibilité</label>
                            <select id="affret_flexibilite" name="flexibilite" class="calc-input">
                                <option value="aucune">Date impérative</option>
                                <option value="1-2j">+/- 1-2 jours</option>
                                <option value="1sem">+/- 1 semaine</option>
                                <option value="flexible">Flexible</option>
                            </select>
                        </div>
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_contact_depart">Contact départ *</label>
                            <input type="text" id="affret_contact_depart" name="contact_depart" class="calc-input" 
                                   placeholder="Nom + téléphone" required>
                        </div>
                        <div class="calc-form-group">
                            <label class="calc-label" for="affret_contact_arrivee">Contact arrivée *</label>
                            <input type="text" id="affret_contact_arrivee" name="contact_arrivee" class="calc-input" 
                                   placeholder="Nom + téléphone" required>
                        </div>
                    </div>
                </div>
                
               <!-- CONTRAINTES -->
                <div class="calc-affret-section">
                    <h3 class="calc-affret-section-title">⚙️ Contraintes</h3>
                    
                    <div class="calc-form-group">
                        <label class="calc-label">Hayon obligatoire *</label>
                        <div class="calc-toggle-group">
                            <button type="button" class="calc-toggle-btn active" data-affret-hayon="non">Non</button>
                            <button type="button" class="calc-toggle-btn" data-affret-hayon="oui">Oui</button>
                        </div>
                        <input type="hidden" id="affret_hayon" name="hayon_obligatoire" value="non">
                    </div>
                    
                    <div class="calc-form-group">
                        <label class="calc-label" for="affret_contraintes">Contraintes spécifiques</label>
                        <textarea id="affret_contraintes" name="contraintes_specifiques" class="calc-textarea" 
                                 placeholder="Ex: Restrictions horaires, accès difficile, produit fragile..." rows="3"></textarea>
                    </div>
                    
                    <div class="calc-form-group">
                        <label class="calc-label" for="affret_commentaires">Commentaires</label>
                        <textarea id="affret_commentaires" name="commentaires" class="calc-textarea" 
                                 placeholder="Informations complémentaires..." rows="3"></textarea>
                    </div>
                </div>
                
                <!-- ACTIONS -->
                <div class="calc-affret-actions">
                    <button type="submit" class="calc-btn-primary calc-btn-large">
                        📧 Envoyer demande
                    </button>
                    <button type="button" class="calc-btn-secondary" onclick="closeAffretement()">
                        🔄 Retour
                    </button>
                    <button type="button" class="calc-btn-contact" onclick="mailLibre()">
                        ✉️ Email libre
                    </button>
                </div>
            </form>
        </section>
        
        <!-- RÉSULTATS -->
        <section class="calc-results-panel" id="resultsPanel">
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
            
            <!-- Information Express Dédié -->
            <div class="calc-express-info">
                <div class="calc-express-header">
                    <div class="calc-express-icon">⚡</div>
                    <div>
                        <div class="calc-express-title">Express Dédié Disponible</div>
                        <div class="calc-express-subtitle">Livraison urgente 12h - Tarif au réel</div>
                    </div>
                </div>
                <div class="calc-express-content">
                    <p>Pour les situations d'urgence, nous proposons un <strong>service express dédié</strong> :</p>
                    <div class="calc-express-example">
                        📦 <strong>Exemple :</strong> Client en rupture de stock<br>
                        🕐 <strong>Délai :</strong> Chargé l'après-midi → Livré lendemain 8h<br>
                        💰 <strong>Coût :</strong> <span class="calc-express-price">600€ - 800€</span> (selon distance)
                    </div>
                    <p>Ce service est <strong>calculé au réel</strong> selon la distance et l'urgence. Il permet de débloquer les situations critiques avec une livraison garantie sous 12h.</p>
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
        </section>
    </main>
</div>

<script src="assets/js/port.js?v=<?= $version_info['build'] ?>"></script>

<?php
require_once ROOT_PATH . '/templates/footer.php';
?>

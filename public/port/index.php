<?php
/**
 * Titre: Calculateur de frais de port - Interface complète avec headers corrigés
 * Chemin: /public/port/index.php
 * Version: 0.5 beta + build auto
 */

// Configuration d'erreurs AVANT tout output
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- GESTION AJAX CALCULATE AVANT TOUT ---
if (isset($_GET['ajax']) && $_GET['ajax'] === 'calculate') {
    header('Content-Type: application/json');
    define('ROOT_PATH', dirname(dirname(__DIR__)));
    require_once __DIR__ . '/../../config/config.php';
    try {
        parse_str(file_get_contents('php://input'), $post_data);
        $params = [
            'departement'   => str_pad(trim($post_data['departement'] ?? ''), 2, '0', STR_PAD_LEFT),
            'poids'         => floatval($post_data['poids'] ?? 0),
            'type'          => strtolower(trim($post_data['type'] ?? 'colis')),
            'adr'           => (($post_data['adr'] ?? 'non') === 'oui'),
            'option_sup'    => trim($post_data['option_sup'] ?? 'standard'),
            'enlevement'    => ($post_data['enlevement'] ?? 'non') === 'oui',
            'palettes'      => max(1, intval($post_data['palettes'] ?? 1)),
            'palette_eur'   => intval($post_data['palette_eur'] ?? 0),
        ];

        // FORÇAGE palette si poids > 60kg
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
        // ADR OBLIGATOIRE
        if ($params['adr'] !== true) {
            throw new Exception('Le transport ADR (matières dangereuses) doit être explicitement sélectionné.');
        }

        // Limite palette
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
            'success'  => true,
            'carriers' => [],
            'time_ms'  => $calc_time,
            'debug'    => $transport->debug ?? null
        ];

        $carrier_names = [
            'xpo'     => 'XPO Logistics',
            'heppner' => 'Heppner',
            'kn'      => 'Kuehne + Nagel'
        ];
        $carrier_results = $results['results'] ?? $results;

        foreach ($carrier_results as $carrier => $price) {
            // Masquer temporairement K+N
            if ($carrier === 'kuehne_nagel' || $carrier === 'kn') continue;
            $response['carriers'][$carrier] = [
                'name'      => $carrier_names[$carrier] ?? strtoupper($carrier),
                'price'     => $price,
                'formatted' => $price ? number_format($price, 2, ',', ' ') . ' €' : 'Non disponible',
                'available' => $price !== null && $price > 0
            ];
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error'   => $e->getMessage(),
            'debug'   => $transport->debug ?? null
        ]);
    }
    exit;
}

// --- GESTION AJAX DELAY ET AFFRETEMENT : inchangés depuis ta version précédente ---

// ... [Tu peux laisser ici les blocs AJAX delay et affretement, non modifiés pour ne pas surcharger la réponse] ...

// ========================================
// PAGE NORMALE - APRÈS TOUTES LES AJAX
// ========================================

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';
// (inclure functions si besoin...)

// Variables pour header/footer
$version_info     = getVersionInfo();
$page_title       = 'Calculateur de Frais de Port';
$page_subtitle    = 'Comparaison multi-transporteurs XPO, Heppner, Kuehne+Nagel';
$page_description = 'Calculateur de frais de port professionnel - Comparaison instantanée des tarifs de transport';
$current_module   = 'port';
$user_authenticated = true;
$module_css = true;
$breadcrumbs = [
    ['icon' => '🏠', 'text' => 'Accueil', 'url' => '/', 'active' => false],
    ['icon' => '🚛', 'text' => 'Calculateur', 'url' => '/port/', 'active' => true]
];

// Chargement header
require_once ROOT_PATH . '/templates/header.php';
?>

<div class="calc-container">
    <main class="calc-main">
        <!-- FORMULAIRE PRINCIPAL AVEC AMELIORATIONS UI -->
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
                            placeholder="Ex: 67, 75, 13..." maxlength="3" required autocomplete="off">
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
                    <div class="calc-limit-warning" id="limitWarning">
                        <div class="calc-limit-icon">⚖️</div>
                        <div class="calc-limit-title">Limite dépassée</div>
                        <div class="calc-limit-text">
                            Au-delà de 3000kg ou 6 palettes, nous devons établir une cotation personnalisée pour l'affrètement.
                        </div>
                        <div class="calc-limit-actions">
                            <a href="tel:+33389634242" class="calc-btn-contact">📞 Appeler 03 89 63 42 42</a>
                            <button type="button" class="calc-btn-contact" onclick="showAffretement()">📋 Formulaire affrètement</button>
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
                    <div class="calc-limit-warning" id="limitPalettesWarning">
                        <div class="calc-limit-icon">🚛</div>
                        <div class="calc-limit-title">Affrètement nécessaire</div>
                        <div class="calc-limit-text">
                            Plus de 6 palettes nécessite un transport dédié avec cotation spécifique.
                        </div>
                        <div class="calc-limit-actions">
                            <a href="tel:+33389634242" class="calc-btn-contact">📞 Appeler 03 89 63 42 42</a>
                            <button type="button" class="calc-btn-contact" onclick="showAffretement()">📋 Formulaire affrètement</button>
                        </div>
                    </div>
                    <div class="calc-form-group calc-group-palette-eur" id="paletteEurGroup" style="display: none;">
                        <label class="calc-label" for="palette_eur">🏷️ Palettes EUR consignées <span class="calc-label-optional">- Facultatif</span></label>
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
                            <button type="button" class="calc-toggle-btn" data-adr="non">❌ Non</button>
                            <button type="button" class="calc-toggle-btn" data-adr="oui">⚠️ Oui</button>
                        </div>
                        <input type="hidden" id="adr" name="adr" value="">
                    </div>
                </div>

                <!-- Étape 3: Options -->
                <div class="calc-form-step" data-step="3">
                    <div class="calc-form-group">
                        <label class="calc-label">Service de livraison</label>
                        <div class="calc-options-grid">
                            <!-- ... cartes options livraison ... (comme avant) ... -->
                        </div>
                    </div>
                    <div class="calc-form-group">
                        <label class="calc-label">Enlèvement à votre adresse</label>
                        <input type="checkbox" id="enlevement" name="enlevement" value="oui">
                        <label for="enlevement">Je souhaite un enlèvement à mon adresse</label>
                        <small class="calc-help">Gratuit chez Heppner, ~25€ chez XPO</small>
                    </div>
                    <div class="calc-form-actions">
                        <button type="submit" class="calc-btn-primary">🧮 Calculer les tarifs</button>
                        <button type="button" class="calc-btn-secondary" onclick="resetForm()">🔄 Nouvelle recherche</button>
                    </div>
                </div>
            </form>
        </section>

        <!-- FORMULAIRE AFFRÈTEMENT SIMPLIFIÉ (inchangé) -->
        <!-- ... toute la partie formulaire affrètement de ta version précédente ... -->

        <!-- RÉSULTATS / EXPRESS DÉDIÉ / Historique / Debug : inchangés ... -->
    </main>
</div>

<script src="assets/js/port.js?v=<?= $version_info['build'] ?>"></script>
<?php require_once ROOT_PATH . '/templates/footer.php'; ?>

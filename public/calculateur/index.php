<?php
/**
 * Titre: Module Calculateur - Interface progressive
 * Chemin: /public/calculateur/index.php
 * Version: 0.5 beta + build
 * 
 * Interface calculateur progressive avec architecture MVC
 */

// =========================================================================
// CONFIGURATION ET SÉCURITÉ
// =========================================================================

// Chargement configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/version.php';
require_once __DIR__ . '/../../src/modules/calculateur/services/transportcalculateur.php';

// Définir BASE_PATH si pas défini
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

// Démarrage session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification accès module (si système d'authentification activé)
if (function_exists('hasModuleAccess') && !hasModuleAccess('calculateur')) {
    header('Location: ../');
    exit('Module calculateur non disponible');
}

// Mode debug
$debug_mode = defined('DEBUG') && DEBUG === true;

// =========================================================================
// RÉCUPÉRATION DES DONNÉES
// =========================================================================

try {
    // Options de service depuis BDD
    $options_service = [];
    $stmt = $db->query("
        SELECT DISTINCT transporteur, code_option, libelle, montant 
        FROM gul_options_supplementaires 
        WHERE actif = 1 
        ORDER BY transporteur, libelle
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $options_service[] = $row;
    }
    
    // Départements avec restrictions (pour validation côté client)
    $dept_restrictions = [];
    $stmt = $db->query("
        SELECT transporteur, departements_blacklistes 
        FROM gul_taxes_transporteurs 
        WHERE departements_blacklistes IS NOT NULL AND departements_blacklistes != ''
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['departements_blacklistes']) {
            $dept_restrictions[$row['transporteur']] = explode(',', $row['departements_blacklistes']);
        }
    }
    
} catch (Exception $e) {
    // Fallback en cas d'erreur BDD
    $options_service = [
        ['transporteur' => 'Tous', 'code_option' => 'standard', 'libelle' => 'Livraison standard', 'montant' => 0],
        ['transporteur' => 'Tous', 'code_option' => 'rdv', 'libelle' => 'Prise de RDV', 'montant' => 15]
    ];
    $dept_restrictions = [];
    error_log("Erreur récupération données calculateur: " . $e->getMessage());
}

// Variables d'affichage
$page_title = 'Calculateur de frais de port';
$version_info = getVersionInfo();

// Présets depuis URL ou session
$preset_data = [
    'departement' => $_GET['dept'] ?? ($_GET['departement'] ?? ($_SESSION['calc_dept'] ?? '')),
    'poids' => $_GET['poids'] ?? ($_SESSION['calc_poids'] ?? ''),
    'type' => $_GET['type'] ?? ($_SESSION['calc_type'] ?? ''),
    'adr' => $_GET['adr'] ?? ($_SESSION['calc_adr'] ?? ''),
    'options' => $_GET['options'] ?? ($_SESSION['calc_options'] ?? []),
    'palettes' => $_GET['palettes'] ?? ($_SESSION['calc_palettes'] ?? ''),
    'enlevement' => isset($_GET['enlevement']) || ($_SESSION['calc_enlevement'] ?? false)
];

// Sauvegarde en session
if (!empty($preset_data['departement'])) $_SESSION['calc_dept'] = $preset_data['departement'];
if (!empty($preset_data['poids'])) $_SESSION['calc_poids'] = $preset_data['poids'];
if (!empty($preset_data['type'])) $_SESSION['calc_type'] = $preset_data['type'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Gul Transport</title>
    
    <!-- Styles CSS -->
    <link rel="stylesheet" href="../assets/css/modules/calculateur/base.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/layout.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/components.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/results.css">
    <link rel="stylesheet" href="../assets/css/modules/calculateur/progressive-form.css">
    
    <!-- Meta SEO -->
    <meta name="description" content="Calculateur de frais de port - Comparez les tarifs des transporteurs">
    <meta name="robots" content="index,follow">
</head>
<body class="calculateur-page">
    
    <!-- Header -->
    <?php include __DIR__ . '/views/partials/header.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="calculateur-container">
            
            <!-- Hero Section -->
            <section class="hero-section">
                <div class="hero-content">
                    <h1 class="hero-title">
                        🧮 Calculateur de frais de port
                    </h1>
                    <p class="hero-subtitle">
                        Comparez instantanément les tarifs des transporteurs
                    </p>
                </div>
            </section>
            
            <!-- Layout principal -->
            <div class="calculator-layout">
                
                <!-- Section paramètres -->
                <section class="parameters-section">
                    <div class="parameters-header">
                        <h2>
                            📦 Paramètres d'expédition
                        </h2>
                        <p>Renseignez les informations de votre envoi</p>
                    </div>
                    
                    <div class="form-steps">
                        <form id="calculator-form" method="post" action="ajax-calculate.php">
                            
                            <!-- Étape 1: Destination -->
                            <div class="form-step" id="step-1">
                                <div class="step-header">
                                    <div class="step-number">1</div>
                                    <h3 class="step-title">Destination</h3>
                                </div>
                                
                                <div class="form-group">
                                    <label for="departement" class="form-label">
                                        📍 Département de livraison
                                    </label>
                                    <input 
                                        type="text" 
                                        id="departement" 
                                        name="departement" 
                                        class="form-control" 
                                        placeholder="Ex: 75"
                                        value="<?= htmlspecialchars($preset_data['departement']) ?>"
                                        pattern="^(0[1-9]|[1-8][0-9]|9[0-5])$"
                                        maxlength="2"
                                        required
                                    >
                                    <div class="field-help">
                                        Saisissez le numéro de département (01 à 95)
                                    </div>
                                </div>
                                
                                <div class="step-summary" id="summary-step-1" style="display: none;">
                                    <div class="summary-title">Destination</div>
                                    <div class="summary-content" id="summary-content-1"></div>
                                </div>
                            </div>
                            
                            <!-- Étape 2: Caractéristiques -->
                            <div class="form-step" id="step-2">
                                <div class="step-header">
                                    <div class="step-number">2</div>
                                    <h3 class="step-title">Caractéristiques</h3>
                                </div>
                                
                                <!-- Poids -->
                                <div class="form-group">
                                    <label for="poids" class="form-label">
                                        ⚖️ Poids total (kg)
                                    </label>
                                    <input 
                                        type="number" 
                                        id="poids" 
                                        name="poids" 
                                        class="form-control" 
                                        placeholder="Ex: 25.5"
                                        value="<?= htmlspecialchars($preset_data['poids']) ?>"
                                        min="0.1"
                                        max="3500"
                                        step="0.1"
                                        required
                                    >
                                    <div class="field-help">
                                        Poids total de votre envoi (0.1 à 3500 kg)
                                    </div>
                                </div>
                                
                                <!-- Type d'envoi -->
                                <div class="form-group">
                                    <label class="form-label">
                                        📦 Type d'envoi
                                    </label>
                                    <div class="radio-group">
                                        <label class="radio-option">
                                            <input 
                                                type="radio" 
                                                name="type" 
                                                value="colis" 
                                                <?= ($preset_data['type'] === 'colis' || empty($preset_data['type'])) ? 'checked' : '' ?>
                                            >
                                            <span class="radio-label">🎁 Colis</span>
                                        </label>
                                        <label class="radio-option">
                                            <input 
                                                type="radio" 
                                                name="type" 
                                                value="palette" 
                                                <?= $preset_data['type'] === 'palette' ? 'checked' : '' ?>
                                            >
                                            <span class="radio-label">🏗️ Palette</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Nombre de palettes (conditionnel) -->
                                <div class="form-group" id="field-palettes" style="display: <?= $preset_data['type'] === 'palette' ? 'block' : 'none' ?>;">
                                    <label for="palettes" class="form-label">
                                        🏗️ Nombre de palettes EUR
                                    </label>
                                    <input 
                                        type="number" 
                                        id="palettes" 
                                        name="palettes" 
                                        class="form-control" 
                                        placeholder="Ex: 2"
                                        value="<?= htmlspecialchars($preset_data['palettes']) ?>"
                                        min="1"
                                        max="26"
                                    >
                                    <div class="field-help">
                                        Nombre de palettes Europe (1 à 26)
                                    </div>
                                </div>
                                
                                <!-- Matières dangereuses -->
                                <div class="form-group">
                                    <label class="form-label">
                                        ⚠️ Matières dangereuses (ADR)
                                    </label>
                                    <div class="radio-group">
                                        <label class="radio-option">
                                            <input 
                                                type="radio" 
                                                name="adr" 
                                                value="non" 
                                                <?= ($preset_data['adr'] === 'non' || empty($preset_data['adr'])) ? 'checked' : '' ?>
                                            >
                                            <span class="radio-label">✅ Non</span>
                                        </label>
                                        <label class="radio-option">
                                            <input 
                                                type="radio" 
                                                name="adr" 
                                                value="oui" 
                                                <?= $preset_data['adr'] === 'oui' ? 'checked' : '' ?>
                                            >
                                            <span class="radio-label">⚠️ Oui</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="step-summary" id="summary-step-2" style="display: none;">
                                    <div class="summary-title">Caractéristiques</div>
                                    <div class="summary-content" id="summary-content-2"></div>
                                </div>
                            </div>
                            
                            <!-- Étape 3: Options -->
                            <div class="form-step" id="step-3">
                                <div class="step-header">
                                    <div class="step-number">3</div>
                                    <h3 class="step-title">Options de service</h3>
                                </div>
                                
                                <!-- Options supplémentaires -->
                                <div class="form-group">
                                    <label class="form-label">
                                        ⚙️ Options supplémentaires
                                    </label>
                                    <div class="checkbox-group">
                                        <?php foreach ($options_service as $option): ?>
                                        <label class="checkbox-option">
                                            <input 
                                                type="checkbox" 
                                                name="options[]" 
                                                value="<?= htmlspecialchars($option['code_option']) ?>"
                                                <?= in_array($option['code_option'], (array)$preset_data['options']) ? 'checked' : '' ?>
                                            >
                                            <span class="checkbox-label">
                                                <?= htmlspecialchars($option['libelle']) ?>
                                                <?php if ($option['montant'] > 0): ?>
                                                <span class="option-price">+<?= number_format($option['montant'], 2) ?>€</span>
                                                <?php endif; ?>
                                            </span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Enlèvement -->
                                <div class="form-group">
                                    <label class="checkbox-option checkbox-primary">
                                        <input 
                                            type="checkbox" 
                                            name="enlevement" 
                                            value="1"
                                            <?= $preset_data['enlevement'] ? 'checked' : '' ?>
                                        >
                                        <span class="checkbox-label">
                                            🚚 Enlèvement à domicile
                                        </span>
                                    </label>
                                </div>
                                
                                <div class="step-summary" id="summary-step-3" style="display: none;">
                                    <div class="summary-title">Options sélectionnées</div>
                                    <div class="summary-content" id="summary-content-3"></div>
                                </div>
                            </div>
                            
                            <!-- Navigation cachée (gérée par JS) -->
                            <div class="step-navigation auto-advance-nav">
                                <button type="button" class="nav-btn secondary" id="btn-previous">
                                    ← Précédent
                                </button>
                                <button type="button" class="nav-btn primary" id="btn-next">
                                    Suivant →
                                </button>
                            </div>
                            
                        </form>
                    </div>
                </section>
                
                <!-- Section résultats -->
                <section class="results-section">
                    <?php include __DIR__ . '/views/components/results-panel.php'; ?>
                </section>
                
            </div>
        </div>
    </main>

    <!-- Actions flottantes -->
    <div class="floating-actions">
        <button type="button" class="floating-btn" id="btn-reset" title="Nouveau calcul">
            🔄
        </button>
        <?php if ($debug_mode): ?>
        <button type="button" class="floating-btn debug" id="btn-debug" title="Debug">
            🐛
        </button>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/views/partials/footer.php'; ?>

    <!-- Scripts JavaScript - Ordre d'importation critique -->
    <script>
    // Configuration serveur pour le JS
    window.CalculateurServerConfig = {
        presetData: <?= json_encode($preset_data) ?>,
        optionsService: <?= json_encode($options_service) ?>,
        deptRestrictions: <?= json_encode($dept_restrictions) ?>,
        debugMode: <?= $debug_mode ? 'true' : 'false' ?>
    };
    </script>
    
    <!-- Scripts JS dans l'ordre de dépendance -->
    <script src="../assets/js/modules/calculateur/core/calculateur-config.js"></script>
    <script src="../assets/js/modules/calculateur/core/state-manager.js"></script>
    <script src="../assets/js/modules/calculateur/controllers/form-controller.js"></script>
    <script src="../assets/js/modules/calculateur/core/api-service.js"></script>
    <script src="../assets/js/modules/calculateur/models/form-data.js"></script>
    <script src="../assets/js/modules/calculateur/models/validation.js"></script>
    <script src="../assets/js/modules/calculateur/controllers/calculation-controller.js"></script>
    <script src="../assets/js/modules/calculateur/controllers/ui-controller.js"></script>
    <script src="../assets/js/modules/calculateur/views/progressive-form.js"></script>
    <script src="../assets/js/modules/calculateur/views/results-display.js"></script>
    <script src="../assets/js/modules/calculateur/main.js"></script>
    
    
    <!-- Initialisation -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Vérification des dépendances
        if (typeof CalculateurApp === 'undefined') {
            console.error('❌ Module principal CalculateurApp non chargé');
            return;
        }
        
        // Initialisation de l'application
        const app = new CalculateurApp();
        app.init(window.CalculateurServerConfig)
            .then(() => {
                console.log('✅ Calculateur initialisé avec succès');
            })
            .catch(error => {
                console.error('❌ Erreur initialisation calculateur:', error);
                
                // Fallback simple en cas d'erreur
                initFallbackMode();
            });
    });
    
    // Mode fallback simple
    function initFallbackMode() {
        console.log('🔄 Activation du mode fallback...');
        
        // Gestion simple du type d'envoi
        const typeRadios = document.querySelectorAll('input[name="type"]');
        const palettesField = document.getElementById('field-palettes');
        
        typeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (palettesField) {
                    palettesField.style.display = this.value === 'palette' ? 'block' : 'none';
                }
            });
        });
        
        // Soumission du formulaire
        const form = document.getElementById('calculator-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Collecte des données
                const formData = new FormData(this);
                
                // Affichage loading
                const resultsPanel = document.querySelector('.results-content');
                if (resultsPanel) {
                    resultsPanel.innerHTML = '<div class="loading">🔄 Calcul en cours...</div>';
                }
                
                // Envoi AJAX
                fetch('ajax-calculate.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && resultsPanel) {
                        // Affichage des résultats (simplifié)
                        let html = '<div class="results-success">';
                        html += '<h3>🎯 Meilleur tarif</h3>';
                        if (data.best) {
                            html += `<div class="best-result">`;
                            html += `<strong>${data.best.transporteur}</strong><br>`;
                            html += `<span class="price">${data.best.prix_total}€</span>`;
                            html += `</div>`;
                        }
                        html += '</div>';
                        resultsPanel.innerHTML = html;
                    } else {
                        resultsPanel.innerHTML = '<div class="error">❌ Erreur de calcul</div>';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    if (resultsPanel) {
                        resultsPanel.innerHTML = '<div class="error">❌ Erreur de connexion</div>';
                    }
                });
            });
        }
    }
    </script>
    <!-- Initialisation du contrôleur après chargement du DOM et des modules -->
<script>
    window.addEventListener('DOMContentLoaded', () => {
        // Attendre que CalculateurConfig soit défini
        const checkReady = () => {
            if (typeof window.CalculateurConfig !== 'undefined' &&
                typeof window.FormController !== 'undefined') {
                window.formController = new window.FormController();
            } else {
                // Réessaie après un petit délai si ce n'est pas prêt
                setTimeout(checkReady, 50);
            }
        };

        checkReady();
    });
</script>

</body>
</html>
